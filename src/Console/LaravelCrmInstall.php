<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;

class LaravelCrmInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel CRM package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Installing LaravelCRM...');

        $this->call('vendor:publish', [
            '--provider' => 'VentureDrake\LaravelCrm\LaravelCrmServiceProvider',
        ]);

        $this->call('db:seed', [
            '--class' => 'LaravelCrmTablesSeeder',
        ]);

        $this->info('Installed LaravelCRM');
    }
}
