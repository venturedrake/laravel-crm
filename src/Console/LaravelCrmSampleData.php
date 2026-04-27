<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmSampleDataSeeder;

class LaravelCrmSampleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:sample-data
                            {--fresh : Truncate all CRM sample data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the CRM with a complete sample dataset';

    /**
     * CRM tables that may be truncated by --fresh, in dependency-safe order
     * (children before parents). The users table is intentionally excluded
     * so any existing users are preserved.
     *
     * @var array<int, string>
     */
    protected $sampleTables = [
        // Custom field values & definitions
        'field_values',
        'field_options',
        'field_models',
        'fields',
        'field_groups',

        // Activities
        'tasks',
        'notes',
        'calls',
        'meetings',
        'lunches',

        // Sales documents
        'deliveries',
        'purchase_orders',
        'invoices',
        'orders',
        'quotes',
        'deals',
        'leads',

        // Customers / contacts / addressables
        'clients',
        'customers',
        'contacts',
        'emails',
        'phones',
        'addresses',
        'files',

        // Catalogue
        'products',
        'product_categories',

        // Core entities
        'people',
        'organisations',
        'organizations',
    ];

    /**
     * Tables that must NEVER be truncated by --fresh, regardless of what
     * appears in $sampleTables. Acts as a defensive safeguard so that
     * existing application users (and their auth-related data) are
     * preserved across sample-data resets.
     *
     * @var array<int, string>
     */
    protected $protectedTables = [
        'users',
        'password_resets',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'failed_jobs',
        'migrations',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->warn('=========================================================');
            $this->warn(' --fresh will TRUNCATE all CRM sample data tables.');
            $this->warn(' Existing users (and their roles/permissions) will be');
            $this->warn(' preserved, but every other CRM record (leads, deals,');
            $this->warn(' organisations, people, etc.) will be permanently');
            $this->warn(' removed.');
            $this->warn('=========================================================');

            if (! $this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Aborted. No changes have been made.');

                return self::SUCCESS;
            }

            $this->truncateSampleTables();
        }

        $this->info('Seeding CRM sample data...');

        $seeder = new LaravelCrmSampleDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Sample data seeding complete.');

        return self::SUCCESS;
    }

    /**
     * Truncate all CRM sample data tables (preserving users).
     */
    protected function truncateSampleTables(): void
    {
        $prefix = config('laravel-crm.db_table_prefix', 'crm_');

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->sampleTables as $table) {
                $name = $prefix.$table;

                // Defensive: never truncate a protected table even if it
                // somehow ended up in $sampleTables, and never touch the
                // raw `users` table no matter the prefix.
                if ($this->isProtected($table) || $this->isProtected($name)) {
                    $this->warn("Skipping protected table: {$name}");

                    continue;
                }

                if (! Schema::hasTable($name)) {
                    continue;
                }

                $this->line("Truncating: {$name}");
                DB::table($name)->truncate();
            }

            // Polymorphic pivot table for labels (no prefix on column).
            if (Schema::hasTable($prefix.'labelables')) {
                $this->line('Truncating: '.$prefix.'labelables');
                DB::table($prefix.'labelables')->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Determine whether a given table name is on the protected list.
     */
    protected function isProtected(string $table): bool
    {
        return in_array($table, $this->protectedTables, true);
    }
}
