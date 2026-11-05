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
     * Add `or column contains term`. Lower-cased on both sides, as only some
     * database collations ignore case.
     */
    public static function orLike(Builder $query, string|Expression $column, string $term): void
    {
        $query->orWhereRaw(
            'lower('.$query->getQuery()->getGrammar()->wrap($column).') like ? escape \''.self::ESCAPE.'\'',
            ['%'.self::escape(Str::lower($term)).'%'],
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

        // Long enough to overflow the column, so it can't be a number.
        if (! preg_match('/^(.*?)(\d{1,18})$/', trim($term), $matches)) {
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
