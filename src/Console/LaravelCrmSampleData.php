<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
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
    protected $description = 'Seed the CRM with realistic sample data spanning 3 years';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            if (! $this->confirm('This will delete all CRM entity data (organizations, people, leads, deals, quotes, orders, invoices, deliveries, purchase orders, activities). Settings, pipelines, labels, and permissions will be preserved. Continue?')) {
                $this->info('Aborted.');

                return 0;
            }
        }

        $this->info('Seeding CRM sample data...');
        $this->newLine();

        $seeder = new LaravelCrmSampleDataSeeder;
        $seeder->setCommand($this);
        $seeder->run($this->option('fresh'));

        $this->newLine();
        $this->info('✅ CRM sample data seeded successfully!');

        return 0;
    }
}

