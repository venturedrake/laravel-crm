<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Models\Permission;
use VentureDrake\LaravelCrm\Observers\TeamObserver;
use VentureDrake\LaravelCrm\Services\SettingService;

class LaravelCrmV2 extends Command
{
    /**
     * v1 tables and their v2 names, without the table prefix.
     *
     * GuardCrmV2Upgrade reads the keys: while any of them exists, the
     * database is still on v1.
     *
     * @var array<string, string>
     */
    public const TABLE_RENAMES = [
        'organisations' => 'organizations',
        'organisation_types' => 'organization_types',
        'clients' => 'customers',
    ];

    /**
     * v1 columns and their v2 names, as [v2 table, v1 column, v2 column].
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    protected const COLUMN_RENAMES = [
        ['people', 'organisation_id', 'organization_id'],
        ['leads', 'organisation_id', 'organization_id'],
        ['deals', 'organisation_id', 'organization_id'],
        ['quotes', 'organisation_id', 'organization_id'],
        ['orders', 'organisation_id', 'organization_id'],
        ['invoices', 'organisation_id', 'organization_id'],
        ['purchase_orders', 'organisation_id', 'organization_id'],
        ['xero_contacts', 'organisation_id', 'organization_id'],
        ['organizations', 'organisation_type_id', 'organization_type_id'],
        ['leads', 'client_id', 'customer_id'],
        ['deals', 'client_id', 'customer_id'],
        ['quotes', 'client_id', 'customer_id'],
        ['orders', 'client_id', 'customer_id'],
        ['customers', 'clientable_type', 'customerable_type'],
        ['customers', 'clientable_id', 'customerable_id'],
    ];

    /**
     * v1 migrations that v2 publishes under a new name for the same schema
     * change, keyed by v1 name suffix (the part after the timestamp).
     *
     * A v1 host has a migrations row for the v1 name only, so without
     * reconciliation the nested `migrate` runs the v2 file again. The
     * `add_client_to_laravel_crm_deals_table` pair is left out on purpose:
     * from 0.19.0 its v1 stub added `url` columns instead of `client_id`, so
     * on many v1 hosts the row exists but the column was never added. Its v2
     * counterpart is guarded and adds `customer_id` only when missing.
     *
     * @var array<string, string>
     */
    public const RENAMED_MIGRATIONS = [
        'create_laravel_crm_organisation_types_table' => 'create_laravel_crm_organization_types_table',
        'create_laravel_crm_clients_table' => 'create_laravel_crm_customers_table',
        'add_name_to_laravel_crm_clients_table' => 'add_name_to_laravel_crm_customers_table',
        'add_client_to_laravel_crm_leads_table' => 'add_customer_to_laravel_crm_leads_table',
        'add_client_to_laravel_crm_orders_table' => 'add_customer_to_laravel_crm_orders_table',
        'add_client_to_laravel_crm_quotes_table' => 'add_customer_to_laravel_crm_quotes_table',
        'add_extra_fields_to_laravel_crm_organisations_table' => 'add_extra_fields_to_laravel_crm_organizations_table',
    ];

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

        $drops = $this->preflight();

        if ($drops === null) {
            return self::FAILURE;
        }

        // Rename v1 tables/columns/morph types BEFORE running v2 migrations and seeders.
        // The v2 migrations reference the renamed tables (e.g. `organizations`, `customers`,
        // `organization_id`, `customer_id`), so they would otherwise fail on a v1 database.
        $this->info('Applying v1 -> v2 database renames...');

        Schema::disableForeignKeyConstraints();

        try {
            $this->dropStrandedV2Schema($drops);
            $this->migratePermissions();
            $this->renameTables();
            $this->renameColumns();
            $this->updatePolymorphicTypes();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->reconcileRenamedMigrations();

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
     * Find v2 tables and columns that already sit next to their v1 counterparts.
     *
     * They appear when the v2 migrations ran before this command (a plain
     * `migrate` on a v1 database), and they would make the renames skip, leaving
     * the v1 data behind. An empty v2 table, or a v2 column that is NULL in every
     * row, holds nothing and is queued to be dropped. Anything else is a conflict
     * an operator has to resolve, so the upgrade stops before changing anything.
     *
     * Read-only. Returns the queued drops, or null when there is a conflict.
     *
     * @return array{tables: list<string>, columns: list<array{0: string, 1: string}>}|null
     */
    protected function preflight(): ?array
    {
        $drops = ['tables' => [], 'columns' => []];
        $conflicts = [];

        foreach (self::TABLE_RENAMES as $from => $to) {
            $fromTable = $this->prefix.$from;
            $toTable = $this->prefix.$to;

            if (! Schema::hasTable($fromTable) || ! Schema::hasTable($toTable)) {
                continue;
            }

            $fromRows = DB::table($fromTable)->count();
            $toRows = DB::table($toTable)->count();

            if ($toRows === 0) {
                $drops['tables'][] = $toTable;
            } else {
                $conflicts[] = "Both {$fromTable} ({$fromRows} rows) and {$toTable} ({$toRows} rows) exist.";
            }
        }

        $v1Names = array_flip(self::TABLE_RENAMES);

        foreach (self::COLUMN_RENAMES as [$table, $from, $to]) {
            // A renamed table is inspected on whichever side survives: the v1
            // table while it is still there, the v2 table once it is renamed.
            $tableName = isset($v1Names[$table]) && Schema::hasTable($this->prefix.$v1Names[$table])
                ? $this->prefix.$v1Names[$table]
                : $this->prefix.$table;

            if (! Schema::hasTable($tableName)
                || ! Schema::hasColumn($tableName, $from)
                || ! Schema::hasColumn($tableName, $to)) {
                continue;
            }

            $fromValues = DB::table($tableName)->whereNotNull($from)->count();
            $toValues = DB::table($tableName)->whereNotNull($to)->count();

            if ($toValues === 0) {
                $drops['columns'][] = [$tableName, $to];
            } else {
                $conflicts[] = "Both {$tableName}.{$from} ({$fromValues} non-null) and {$tableName}.{$to} ({$toValues} non-null) exist.";
            }
        }

        if ($conflicts !== []) {
            $this->error('The database holds both v1 and v2 versions of these CRM tables or columns, and the v2 side has data:');

            foreach ($conflicts as $conflict) {
                $this->error('  '.$conflict);
            }

            $this->error('Nothing has been changed. Decide which side to keep, remove the other (or restore your backup), then re-run php artisan laravelcrm:v2.');

            return null;
        }

        return $drops;
    }

    /**
     * Drop the empty v2 tables and all-NULL v2 columns preflight() queued.
     *
     * @param  array{tables: list<string>, columns: list<array{0: string, 1: string}>}  $drops
     */
    protected function dropStrandedV2Schema(array $drops): void
    {
        foreach ($drops['tables'] as $table) {
            $this->line("Dropping empty v2 table: {$table}");
            Schema::drop($table);
        }

        foreach ($drops['columns'] as [$table, $column]) {
            $this->line("Dropping empty v2 column: {$table}.{$column}");
            $this->dropColumnWithIndexes($table, $column);
        }
    }

    /**
     * Drop a column after the indexes and foreign keys that include it, which
     * SQLite and MySQL both refuse to drop the column out from under.
     */
    protected function dropColumnWithIndexes(string $table, string $column): void
    {
        $indexes = collect(Schema::getIndexes($table))
            ->filter(fn ($index) => ! $index['primary'] && in_array($column, $index['columns'], true))
            ->pluck('name');

        $foreignKeys = collect(Schema::getForeignKeys($table))
            ->filter(fn ($foreignKey) => in_array($column, $foreignKey['columns'], true));

        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table($table, function (Blueprint $blueprint) use ($column, $indexes, $foreignKeys, $sqlite) {
            foreach ($foreignKeys as $foreignKey) {
                // SQLite foreign keys have no name; it drops them by column.
                $blueprint->dropForeign($sqlite ? $foreignKey['columns'] : $foreignKey['name']);
            }

            foreach ($indexes as $index) {
                $blueprint->dropIndex($index);
            }

            $blueprint->dropColumn($column);
        });
    }

    /**
     * Rename v1 tables to their v2 names.
     */
    protected function renameTables(): void
    {
        foreach (self::TABLE_RENAMES as $from => $to) {
            $fromTable = $this->prefix.$from;
            $toTable = $this->prefix.$to;

            if (Schema::hasTable($fromTable) && ! Schema::hasTable($toTable)) {
                $this->line("Renaming table: {$fromTable} -> {$toTable}");
                Schema::rename($fromTable, $toTable);
            }
        }
    }

    /**
     * Rename v1 columns to their v2 names. Runs after renameTables().
     */
    protected function renameColumns(): void
    {
        foreach (self::COLUMN_RENAMES as [$table, $from, $to]) {
            $this->renameColumn($this->prefix.$table, $from, $to);
        }
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
     * Mark the v2 name of each renamed v1 migration as run, in the v1 row's batch.
     *
     * The v1 row is kept. If the v1 file is still in the host's
     * database/migrations, deleting its row would make `migrate` run it again.
     */
    protected function reconcileRenamedMigrations(): void
    {
        $repository = app('migration.repository');

        if (! $repository->repositoryExists()) {
            return;
        }

        $batches = $repository->getMigrationBatches();
        $ran = collect(array_keys($batches));

        // The names laravelcrm:update is about to publish: the host's existing
        // file where there is one, otherwise the provider's minted name.
        $published = collect(ServiceProvider::pathsToPublish(LaravelCrmServiceProvider::class, 'migrations'))
            ->map(fn ($path) => basename($path, '.php'))
            ->values();

        foreach (self::RENAMED_MIGRATIONS as $v1 => $v2) {
            $v1Migration = $ran->first(fn ($name) => preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_'.preg_quote($v1, '/').'$/', $name));

            if ($v1Migration === null || $ran->contains(fn ($name) => str_ends_with($name, '_'.$v2))) {
                continue;
            }

            $v2Migration = $published->first(fn ($name) => str_ends_with($name, '_'.$v2));

            if ($v2Migration === null) {
                continue;
            }

            $this->line("Marking migration as run: {$v2Migration} (already applied as {$v1Migration})");

            $repository->log($v2Migration, $batches[$v1Migration]);
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
