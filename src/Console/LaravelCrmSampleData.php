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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->warn('=========================================================');
            $this->warn(' --fresh will TRUNCATE all CRM sample data tables.');
            $this->warn(' Existing users will be preserved, but every other CRM');
            $this->warn(' record (leads, deals, organisations, people, etc.)');
            $this->warn(' will be permanently removed.');
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
}

