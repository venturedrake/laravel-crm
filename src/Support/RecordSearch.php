<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The search terms a list or board puts into SQL.
 *
 * Terms are matched literally, so `%` finds a percent sign rather than every row.
 * The escape character is `!` because a backslash needs different quoting on each
 * database, and SQLite has no default escape character at all.
 */
class RecordSearch
{
    private const ESCAPE = '!';

    /**
     * The `number` column is a 32-bit integer on most tables.
     */
    private const MAX_NUMBER = 2147483647;

    /**
     * Add `or column contains term`. Lower-cased on both sides, as only some
     * database collations ignore case. The term is also matched as typed, as
     * SQLite and C-locale Postgres only lower-case ASCII in the database, so
     * lower(`Élodie`) there would never equal the `élodie` PHP makes of the term.
     */
    public static function orLike(Builder $query, string|Expression $column, string $term): void
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);
        $escape = ' escape \''.self::ESCAPE.'\'';

        $query->orWhereRaw(
            '(lower('.$wrapped.') like ?'.$escape.' or '.$wrapped.' like ?'.$escape.')',
            ['%'.self::escape(Str::lower($term)).'%', '%'.self::escape($term).'%'],
        );
    }

    /**
     * Add `or the record's number matches term`, for a model whose number is in a
     * `{model}_id` column built from `prefix` + `number`, such as QU-3002.
     *
     * The stored number is matched as well as its parts, because each record keeps
     * the prefix it was created with and the setting can change after that. A
     * record stored with no prefix or number displays the current prefix and its
     * id, so those are what it answers to.
     */
    public static function orNumber(Builder $query, string $term): void
    {
        $model = $query->getModel();
        $key = Str::snake(class_basename($model));

        self::orLike($query, $model->qualifyColumn($key.'_id'), $term);

        // The lookbehind keeps a long run of digits from being retried at every
        // position, as nothing limits the length of a search term.
        //
        // Too big for the column, so it can't be a number: Postgres raises an error
        // for the comparison rather than finding nothing. The few bigint numbers
        // beyond this still match through `{model}_id` above.
        if (! preg_match('/^(.*?)(?<!\d)(\d+)$/', trim($term), $matches) || (float) $matches[2] > self::MAX_NUMBER) {
            return;
        }

        [, $prefix, $digits] = $matches;

        $query->orWhere(function (Builder $query) use ($model, $key, $prefix, $digits) {
            $query->where(fn (Builder $query) => $query
                ->where($model->qualifyColumn('number'), $digits)
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull($model->qualifyColumn('number'))
                    ->whereKey($digits)));

            if ($prefix === '') {
                return;
            }

            $column = $model->qualifyColumn('prefix');
            $isCurrentPrefix = strcasecmp($prefix, (string) app('laravel-crm.settings')->get($key.'_prefix')) === 0;

            $query->where(fn (Builder $query) => $query
                ->whereRaw('lower('.$query->getQuery()->getGrammar()->wrap($column).') = ?', [Str::lower($prefix)])
                ->when($isCurrentPrefix, fn (Builder $query) => $query->orWhereNull($column)));
        });
    }

    private static function escape(string $term): string
    {
        return str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            $term,
        );
    }
}
