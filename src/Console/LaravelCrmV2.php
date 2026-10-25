<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Permission;
use VentureDrake\LaravelCrm\Observers\TeamObserver;
use VentureDrake\LaravelCrm\Services\SettingService;

class LaravelCrmV2 extends Command
{
    /**
     * @var SettingService
     */
    private $settingService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Laravel CRM package to version 2.x';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Configured CRM table prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Composer $composer, SettingService $settingService)
    {
        parent::__construct();
        $this->composer = $composer;
        $this->settingService = $settingService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->warn('=========================================================');
        $this->warn(' WARNING: This command will modify your CRM database.');
        $this->warn(' It renames tables and columns and rewrites polymorphic');
        $this->warn(' class references as part of the v1 -> v2 upgrade.');
        $this->warn('');
        $this->warn(' Please take a FULL DATABASE BACKUP before continuing.');
        $this->warn('=========================================================');

        if (! $this->confirm('Have you taken a backup and do you want to continue?', false)) {
            $this->info('Aborted. No changes have been made.');

            return self::SUCCESS;
        }

        $this->prefix = config('laravel-crm.db_table_prefix', 'crm_');

        // Rename v1 tables/columns/morph types BEFORE running v2 migrations and seeders.
        // The v2 migrations reference the renamed tables (e.g. `organizations`, `customers`,
        // `organization_id`, `customer_id`), so they would otherwise fail on a v1 database.
        $this->info('Applying v1 -> v2 database renames...');

        Schema::disableForeignKeyConstraints();

        try {
            $this->migratePermissions();
            $this->renameTables();
            $this->renameOrganisationColumns();
            $this->renameClientColumns();
            $this->renameCustomerableMorph();
            $this->updatePolymorphicTypes();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('v1 -> v2 renames complete. Publishing and running v2 migrations...');

        $this->call('laravelcrm:update');

        // v2 upgraders may also have flipped `LARAVEL_CRM_TEAMS=true` during the
        // upgrade. Unconditionally back-fill per-team CRM lookup data + pipelines
        // for every pre-existing team so v2 upgraders end up in the same state as
        // update-command users. No db_update_1201 marker guard is used — both
        // helpers are idempotent (seedCrmDataForTeam upserts every block on
        // team_id + the row's natural key, and repointCrmRecordsToTeamPipelines
        // is a no-op once records already point at per-team stages), so
        // re-running laravelcrm:v2 does not duplicate rows or re-migrate
        // already-migrated records.
        if (config('permission.teams')) {
            $teamClass = class_exists('App\Models\Team')
                ? 'App\Models\Team'
                : (class_exists('App\Team') ? 'App\Team' : null);

            if ($teamClass !== null) {
                $this->info('Backfilling per-team CRM data for existing teams...');

                foreach ($teamClass::all() as $team) {
                    TeamObserver::seedCrmDataForTeam($team->id);
                    TeamObserver::repointCrmRecordsToTeamPipelines($team->id);
                }
            } else {
                $this->warn('Teams enabled but no Team model found at App\Models\Team or App\Team. Skipping per-team backfill.');
            }
        }

        $this->info('Laravel CRM is now updated to version 2.');
    }

    /**
     * Rename "organisations" permissions to "organizations".
     */
    protected function migratePermissions(): void
    {
        foreach (Permission::where('name', 'like', '%organisations%')->cursor() as $permission) {
            $this->line('Updating permission: '.$permission->name);

            $permission->update([
                'name' => str_replace('organisations', 'organizations', $permission->name),
            ]);
        }
    }

    /**
     * Rename v1 tables to their v2 names.
     */
    protected function renameTables(): void
    {
        $renames = [
            'organisations' => 'organizations',
            'organisation_types' => 'organization_types',
            'clients' => 'customers',
        ];

        foreach ($renames as $from => $to) {
            $fromTable = $this->prefix.$from;
            $toTable = $this->prefix.$to;

            if (Schema::hasTable($fromTable) && ! Schema::hasTable($toTable)) {
                $this->line("Renaming table: {$fromTable} -> {$toTable}");
                Schema::rename($fromTable, $toTable);
            }
        }
    }

    /**
     * Rename `organisation_id` columns to `organization_id` and `organisation_type_id` to `organization_type_id`.
     */
    protected function renameOrganisationColumns(): void
    {
        $tables = [
            'people',
            'leads',
            'deals',
            'quotes',
            'orders',
            'invoices',
            'purchase_orders',
            'xero_contacts',
        ];

        foreach ($tables as $table) {
            $this->renameColumn($this->prefix.$table, 'organisation_id', 'organization_id');
        }

        // organization_type_id lives on the (now-renamed) organizations table
        $this->renameColumn($this->prefix.'organizations', 'organisation_type_id', 'organization_type_id');
    }

    /**
     * Rename `client_id` columns to `customer_id` on entity tables.
     */
    protected function renameClientColumns(): void
    {
        foreach (['leads', 'deals', 'quotes', 'orders'] as $table) {
            $this->renameColumn($this->prefix.$table, 'client_id', 'customer_id');
        }
    }

    /**
     * Rename the polymorphic `clientable_*` morph columns to `customerable_*` on the customers table.
     */
    protected function renameCustomerableMorph(): void
    {
        $table = $this->prefix.'customers';

        if (! Schema::hasTable($table)) {
            return;
        }

        $this->renameColumn($table, 'clientable_type', 'customerable_type');
        $this->renameColumn($table, 'clientable_id', 'customerable_id');
    }

    /**
     * Replace v1 model class names persisted in the database with their v2 equivalents.
     *
     * No morph map is registered, so every polymorphic `*_type` column stores the
     * full class name, as does `field_models.model`. Matching is exact, so
     * `Person` and every other class is left alone. Tables and columns a host
     * does not have are skipped: `audits` only exists where owen-it/laravel-auditing
     * was installed, which v2 no longer uses. `audits.old_values` / `new_values`
     * are history and are deliberately not rewritten.
     */
    protected function updatePolymorphicTypes(): void
    {
        $replacements = [
            'VentureDrake\\LaravelCrm\\Models\\Organisation' => 'VentureDrake\\LaravelCrm\\Models\\Organization',
            'VentureDrake\\LaravelCrm\\Models\\Client' => 'VentureDrake\\LaravelCrm\\Models\\Customer',
        ];

        $targets = [
            // [table, class-name column]
            [$this->prefix.'emails', 'emailable_type'],
            [$this->prefix.'phones', 'phoneable_type'],
            [$this->prefix.'addresses', 'addressable_type'],
            [$this->prefix.'field_values', 'field_valueable_type'],
            [$this->prefix.'notes', 'noteable_type'],
            [$this->prefix.'contacts', 'contactable_type'],
            [$this->prefix.'contacts', 'entityable_type'],
            [$this->prefix.'files', 'fileable_type'],
            [$this->prefix.'customers', 'customerable_type'],
            [$this->prefix.'labelables', $this->prefix.'labelable_type'],
            [$this->prefix.'tasks', 'taskable_type'],
            [$this->prefix.'calls', 'callable_type'],
            [$this->prefix.'meetings', 'meetingable_type'],
            [$this->prefix.'lunches', 'lunchable_type'],
            [$this->prefix.'activities', 'timelineable_type'],
            [$this->prefix.'activities', 'recordable_type'],
            [$this->prefix.'activities', 'causeable_type'],
            [$this->prefix.'field_models', 'model'],
            ['audits', 'auditable_type'],
        ];

        foreach ($targets as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            foreach ($replacements as $from => $to) {
                $count = DB::table($table)->where($column, $from)->count();

                if ($count > 0) {
                    $this->line("Updating {$table}.{$column}: {$from} -> {$to} ({$count} rows)");
                    DB::table($table)->where($column, $from)->update([$column => $to]);
                }
            }
        }
    }

    /**
     * Idempotently rename a column on a table if it exists and the target does not.
     */
    protected function renameColumn(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, $from)) {
            return;
        }

        if (Schema::hasColumn($table, $to)) {
            return;
        }

        $this->line("Renaming column: {$table}.{$from} -> {$to}");

        Schema::table($table, function ($t) use ($from, $to) {
            $t->renameColumn($from, $to);
        });
    }
}
