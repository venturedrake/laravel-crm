<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Clears the CRM's references to a user so the user row can be deleted.
 *
 * The CRM tables point at users with plain foreign keys (no ON DELETE action), so
 * deleting anyone who has a personal setting, created a record or holds a team seat
 * fails with an integrity violation. The foreign keys are read from the live schema
 * rather than listed here, because which ones exist depends on which stubs a host
 * has published over the years.
 *
 * Nothing is changed while a required reference (crm_teams.user_id, the team owner)
 * still points at the user: that reference blocks the delete anyway, and callers
 * such as Filament's DeleteAction or a host's profile page delete outside a
 * transaction, so a partial cleanup would be left behind for a user who survives.
 */
class UserReferences
{
    public static function release(Model $user): void
    {
        $id = $user->getKey();
        $connection = $user->getConnection();
        $schema = $connection->getSchemaBuilder();
        $tablePrefix = $connection->getTablePrefix();
        $crmPrefix = config('laravel-crm.db_table_prefix');

        // Rows that only exist for this user go with it. Nulling a settings user_id
        // instead would promote the user's personal preference to a global setting.
        $owned = collect([$crmPrefix.'settings', 'crm_team_user'])
            ->filter(fn ($table) => $schema->hasColumn($table, 'user_id'));

        // getTables() returns unqualified names (and, on Laravel 12, may repeat a name
        // from another schema), so de-duplicate before querying the current schema.
        $tables = collect($schema->getTables())
            ->pluck('name')
            ->unique()
            ->filter(fn ($table) => str_starts_with($table, $tablePrefix))
            ->map(fn ($table) => substr($table, strlen($tablePrefix)))
            ->filter(fn ($table) => Str::startsWith($table, [$crmPrefix, 'crm_']));

        $nullable = [];

        foreach ($tables as $table) {
            $columns = collect($schema->getForeignKeys($table))
                ->filter(fn ($foreignKey) => strcasecmp($foreignKey['foreign_table'], $tablePrefix.$user->getTable()) === 0)
                ->filter(fn ($foreignKey) => count($foreignKey['columns']) === 1)
                ->map(fn ($foreignKey) => $foreignKey['columns'][0])
                ->reject(fn ($column) => $column === 'user_id' && $owned->contains($table));

            if ($columns->isEmpty()) {
                continue;
            }

            $nullableColumns = collect($schema->getColumns($table))->where('nullable', true)->pluck('name');

            foreach ($columns->diff($nullableColumns) as $column) {
                if ($connection->table($table)->where($column, $id)->exists()) {
                    return;
                }
            }

            $nullable[$table] = $columns->intersect($nullableColumns);
        }

        foreach ($owned as $table) {
            $connection->table($table)->where('user_id', $id)->delete();
        }

        foreach ($nullable as $table => $columns) {
            foreach ($columns as $column) {
                $connection->table($table)->where($column, $id)->update([$column => null]);
            }
        }
    }
}
