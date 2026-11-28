<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Task;

/**
 * The order activity lists are shown in, from the `activity_order` setting:
 * by when each activity was entered (the default), or by when it happened.
 *
 * "When it happened" is the date the user gave the activity. An activity
 * without one, or of a type that has none, falls back to when it was entered.
 */
class ActivityOrder
{
    public const LOGGED = 'logged';

    public const OCCURRED = 'occurred';

    /**
     * The columns that say when each type of activity happened; the first
     * one that is filled wins. A task happened when it was completed, and
     * until then on the date it is due.
     *
     * @var array<class-string<EloquentModel>, array<int, string>>
     */
    public const OCCURRED_COLUMNS = [
        Note::class => ['noted_at'],
        Task::class => ['completed_at', 'due_at'],
        Call::class => ['start_at'],
        Meeting::class => ['start_at'],
        Lunch::class => ['start_at'],
    ];

    public static function setting(): string
    {
        return app('laravel-crm.settings')->get('activity_order') === self::OCCURRED
            ? self::OCCURRED
            : self::LOGGED;
    }

    public static function byOccurrence(): bool
    {
        return self::setting() === self::OCCURRED;
    }

    /**
     * Order a query on one type of activity (notes, calls, ...), newest first.
     *
     * @param  Builder|Relation  $query
     */
    public static function applyToRecords($query)
    {
        $columns = self::OCCURRED_COLUMNS[get_class($query->getModel())] ?? [];

        if ($columns && self::byOccurrence()) {
            $table = $query->getModel()->getTable();

            $query->orderByRaw('COALESCE('.self::wrapColumns($query, $table, [...$columns, 'created_at']).') DESC');
        }

        return $query->latest();
    }

    /**
     * Order a query on the activity log, newest first. Each entry points at
     * a record of some type, so its date is read from that type's table.
     *
     * @param  Builder|Relation  $query
     */
    public static function applyToActivities($query)
    {
        if (self::byOccurrence()) {
            $activities = $query->getModel()->getTable();
            $cases = '';
            $bindings = [];

            foreach (self::OCCURRED_COLUMNS as $class => $columns) {
                $record = new $class;
                $date = self::wrapColumns($query, $record->getTable(), $columns);

                $cases .= ' WHEN '.self::wrap($query, $activities.'.recordable_type').' = ? THEN (SELECT '
                    .(count($columns) > 1 ? 'COALESCE('.$date.')' : $date).' FROM '.self::wrap($query, $record->getTable(), true)
                    .' WHERE '.self::wrap($query, $record->getTable().'.id').' = '.self::wrap($query, $activities.'.recordable_id').')';
                $bindings[] = $record->getMorphClass();
            }

            $query->orderByRaw(
                'COALESCE(CASE'.$cases.' END, '.self::wrap($query, $activities.'.created_at').') DESC',
                $bindings
            );
        }

        return $query->latest();
    }

    /**
     * The date to show beside an activity log entry, matching the order.
     */
    public static function dateFor(Activity $activity): ?Carbon
    {
        if (self::byOccurrence() && $activity->recordable) {
            foreach (self::OCCURRED_COLUMNS[get_class($activity->recordable)] ?? [] as $column) {
                if ($activity->recordable->{$column}) {
                    return $activity->recordable->{$column};
                }
            }
        }

        return $activity->created_at;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private static function wrapColumns($query, string $table, array $columns): string
    {
        return implode(', ', array_map(fn ($column) => self::wrap($query, $table.'.'.$column), $columns));
    }

    private static function wrap($query, string $name, bool $table = false): string
    {
        $grammar = $query->getQuery()->getGrammar();

        return $table ? $grammar->wrapTable($name) : $grammar->wrap($name);
    }
}
