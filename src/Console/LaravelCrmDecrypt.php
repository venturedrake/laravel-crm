<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;

class LaravelCrmDecrypt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:decrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypt Laravel CRM database fields';

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
        $this->info('Decrypting Laravel CRM database fields...');

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
                    $encryptedValue = $record->getOriginal($field);
                    if ($encryptedValue) {
                        try {
                            $decryptedValue = decrypt($encryptedValue);
                            $record->$field = $decryptedValue;
                            $record->saveQuietly();
                        } catch (\Exception $e) {
                            $this->error('Failed to decrypt field '.$field.' for '.class_basename($record).' #'.$record->id.': '.$e->getMessage());
                        }
                    }
                }
            }
        }

        $this->info('Laravel CRM database fields decrypted successfully.');
    }
}
