<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

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
        $this->warn('**************************************************************************');
        $this->warn('*                 WELCOME TO THE LARAVEL CRM INSTALLER                   *');
        $this->warn('*                                                                        *');
        $this->warn('*    This CRM package has been designed with security and data privacy   *');
        $this->warn('*    best practices. Depending on the settings you select during the     *');
        $this->warn('*    installation the package will even encrypt private data at table    *');
        $this->warn('*    field level. As a CRM will store private data it is important that  *');
        $this->warn('*    your software is secure.                                            *');
        $this->warn('*                                                                        *');
        $this->warn('*    The developers of this package accept no liability for compromised  *');
        $this->warn('*    data as a result of your software not following the various         *');
        $this->warn('*    security best practices.                                            *');
        $this->warn('*                                                                        *');
        $this->warn('*    To find out more contact me at andrew@laravelcrm.com                *');
        $this->warn('**************************************************************************');

        $confirmed = $this->confirm('I understand, lets proceed 🚀');

        if (! $confirmed) {
            $this->info('😔 Understood, if you have concerns, please reach out to us on Discord, https://discord.gg/YVdwhcqK');

            return;
        }

        $this->info('Checking requirements...');

        $this->info('Checking user authentication...');
        if (! class_exists('App\Models\User') && ! class_exists('App\User')) {
            $this->error('Laravel CRM requires the user model, See https://laravel.com/docs/authentication');

            return;
        }

        $this->info('Checking user authentication passed.');

        // TBC: Check if audits table exists already
        // TBC: Check if spatie permissions tables exists already

        $this->info('Checking requirements passed.');

        $this->info('Installing Laravel CRM...');

        $this->info('Publishing configuration...');

        if (! $this->configExists('laravel-crm')) {
            $this->publishConfiguration();
        } else {
            if ($this->shouldOverwriteConfig()) {
                $this->info('Overwriting configuration file...');
                $this->publishConfiguration($force = true);
            } else {
                $this->info('Existing configuration was not overwritten');
            }
        }

        $this->info('Publishing migrations...');

        $this->callSilent('vendor:publish', [
            '--provider' => 'VentureDrake\LaravelCrm\LaravelCrmServiceProvider',
            '--tag' => 'migrations',
        ]);

        $this->info('Publishing assets...');

        $this->callSilent('vendor:publish', [
            '--provider' => 'VentureDrake\LaravelCrm\LaravelCrmServiceProvider',
            '--tag' => 'assets',
            '--force' => true,
        ]);

        $this->info('Composer dump autoload');
        $this->composer->dumpAutoloads();

        $this->info('Setting up database...');
        $this->call('migrate');
        $this->callSilent('db:seed', [
            '--class' => 'VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder',
        ]);

        if (\App\User::where('crm_access')->count() < 1) {
            $this->info('Create your default owner user');

            $firstname = $this->ask('Whats your first name?');
            $lastname = $this->ask('Whats your last name?');
            $email = $this->ask('Whats your email address?');
            $password = $this->secret('Enter a password');

            if ($user = \App\User::where('email', $email)->first()) {
                $this->info('User already exists, granting crm access...');

                $user->update([
                    'crm_access' => 1,
                ]);

                if (! $user->hasRole('Owner')) {
                    $user->assignRole('Owner');
                }

                $this->info('User access and role updated.');
            } else {
                $user = \App\User::forceCreate([
                    'name' => trim($firstname.' '.$lastname),
                    'email' => $email,
                    'password' => Hash::make($password),
                    'crm_access' => 1,
                ]);

                $user->assignRole('Owner');

                $this->info('User created with owner role');
            }
        }

        $this->info('Laravel CRM is now installed.');

        if ($this->confirm('Would you like to show some love by starring the repo?')) {
            $exec = PHP_OS_FAMILY === 'Windows' ? 'start' : 'open';

            exec("{$exec} https://github.com/venturedrake/laravel-crm");

            $this->line("Thanks for the love.");
        }
    }

    /**
     * Checks if config exists given a filename.
     *
     * @param  string  $fileName
     * @return bool
     */
    private function configExists($fileName): bool
    {
        if (! File::isDirectory(config_path($fileName))) {
            return false;
        }

        return ! empty(File::allFiles(config_path($fileName)));
    }

    /**
     * Returns a prompt if config exists and ask to override it.
     *
     * @return bool
     */
    private function shouldOverwriteConfig(): bool
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    /**
     * Publishes configuration for the Service Provider.
     *
     * @param  bool  $forcePublish
     * @return void
     */
    private function publishConfiguration($forcePublish = false): void
    {
        $params = [
            '--provider' => "VentureDrake\LaravelCrm\LaravelCrmServiceProvider",
            '--tag' => 'config',
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }
}
