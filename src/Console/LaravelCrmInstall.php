<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;

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
     * The Composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Installing LaravelCRM...');

        $this->comment('Publishing package assets');
        $this->callSilent('vendor:publish', [
            '--provider' => 'VentureDrake\LaravelCrm\LaravelCrmServiceProvider',
        ]);

        $this->comment('Composer dump autoload');
        $this->composer->dumpAutoloads();

        $this->call('migrate');

        $this->comment('Seeding database');
        $this->callSilent('db:seed', [
             '--class' => 'VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder',

        ]);
        
        $this->info('Installed LaravelCRM');
    }
}
