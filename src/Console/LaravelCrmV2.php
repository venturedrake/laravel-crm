<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use VentureDrake\LaravelCrm\Models\Permission;
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
        $this->info('Updating Laravel CRM to version 2...');

        foreach (Permission::where('name', 'like', '%organisations%')->cursor() as $permission) {
            $this->line('Updating permission: '.$permission->name);

            $permission->update([
                'name' => str_replace('organisations', 'organizations', $permission->name),
            ]);
        }

        $this->info('Laravel CRM is now updated to version 2.');
    }
}
