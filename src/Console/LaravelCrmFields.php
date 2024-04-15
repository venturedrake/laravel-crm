<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Services\SettingService;

class LaravelCrmFields extends Command
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
    protected $signature = 'laravelcrm:fields';

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
        $this->info('Updating Laravel CRM custom fields...');

        foreach(FieldModel::all() as $fieldModel) {
            $this->line('Updating field: ' . $fieldModel->field->name);

            foreach ($fieldModel->model::all() as $model) {
                $this->line('Updating attached '.class_basename($model).' model #' .$model->id);
                $model->fields()->firstOrCreate([
                    'field_id' => $fieldModel->field_id,
                    'value' => $fieldModel->field->default,
                ]);
            }
        }

        $this->info('Laravel CRM custom fields update complete.');
    }
}
