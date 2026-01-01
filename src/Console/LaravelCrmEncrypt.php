<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Services\SettingService;

class LaravelCrmEncrypt extends Command
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
    protected $signature = 'laravelcrm:encrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt Laravel CRM database fields';

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
        $this->info('Encrypting Laravel CRM database fields...');

        $models = [
            Person::class,
            Email::class,
            Phone::class,
            Address::class,
            Organization::class,
        ];

        foreach ($models as $model) {
            $this->info('Processing '.class_basename($model).' records...');
            $records = $model::withTrashed()->cursor();

            foreach ($records as $record) {
                $this->line('Processing '.class_basename($record).' #'.$record->id.' record.');

                foreach ($record->getEncryptable() as $field) {
                    $unencryptedValue = $record->getOriginal($field);
                    if ($unencryptedValue) {
                        try {
                            $encryptedValue = encrypt($unencryptedValue);
                            $record->$field = $encryptedValue;
                            $record->saveQuietly();
                        } catch (\Exception $e) {
                            $this->error('Failed to encrypt field '.$field.' for '.class_basename($record).' #'.$record->id.': '.$e->getMessage());
                        }
                    }
                }
            }
        }

        $this->info('Laravel CRM database fields encrypted successfully.');
    }
}
