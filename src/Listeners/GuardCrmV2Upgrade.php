<?php

namespace VentureDrake\LaravelCrm\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use VentureDrake\LaravelCrm\Console\LaravelCrmV2;

/**
 * Refuses to run the v2 migrations against a CRM database that is still on v1.
 *
 * Running `migrate` (or `laravelcrm:update`) before `laravelcrm:v2` creates
 * empty v2 tables (`organizations`, `customers`, ...) next to the v1 ones, and
 * the renames in laravelcrm:v2 then skip them, stranding the v1 data.
 *
 * CommandStarting fires only for top-level commands, so the nested `migrate`
 * and `laravelcrm:update` that laravelcrm:v2 itself runs after its renames are
 * never blocked. Only the default connection is checked.
 */
class GuardCrmV2Upgrade
{
    /**
     * @var list<string>
     */
    private const COMMANDS = ['migrate', 'laravelcrm:update'];

    public function handle(CommandStarting $event): void
    {
        if (! in_array($event->command, self::COMMANDS, true)) {
            return;
        }

        try {
            if (! app('migration.repository')->repositoryExists()) {
                return;
            }
        } catch (Throwable) {
            // No database yet: let `migrate` offer to create it.
            return;
        }

        $prefix = config('laravel-crm.db_table_prefix');

        foreach (array_keys(LaravelCrmV2::TABLE_RENAMES) as $table) {
            if (Schema::hasTable($prefix.$table)) {
                throw new RuntimeException(
                    "The CRM database is still on v1 (table `{$prefix}{$table}` exists). Take a full database backup "
                    .'and run `php artisan laravelcrm:v2` first. It renames the v1 tables and then runs the v2 migrations '
                    ."itself. Running `{$event->command}` first would create empty v2 tables next to the v1 ones."
                );
            }
        }
    }
}
