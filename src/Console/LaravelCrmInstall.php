<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LaravelCrmInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:install
                           {--owner-email= : Owner email address (skips prompt)}
                           {--owner-name= : Owner full name e.g. "Jane Smith" (skips prompt)}
                           {--owner-password= : Owner password (skips prompt)}
                           {--enable-teams : Enable multi-tenancy without prompting}
                           {--enable-encryption : Enable sensitive field encryption without prompting}';

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
        $this->warn('*    installation the package will encrypt private data at table         *');
        $this->warn('*    field level. As a CRM will store private data it is important that  *');
        $this->warn('*    your software and any PII is secure.                                *');
        $this->warn('*                                                                        *');
        $this->warn('*    The developers of this package accept no liability for compromised  *');
        $this->warn('*    data as a result of your software not following the various         *');
        $this->warn('*    security best practices.                                            *');
        $this->warn('*                                                                        *');
        $this->warn('*    To find out more contact me at andrew@laravelcrm.com                *');
        $this->warn('**************************************************************************');

        $confirmed = $this->isInteractive()
            ? $this->confirm('I understand, lets proceed 🚀')
            : true;

        if (! $confirmed) {
            $this->info('😔 Understood, if you have concerns, please reach out to us on Discord, https://discord.gg/YVdwhcqK');

            return;
        }

        // Clear cached config/routes up-front so any stale `config:cache` from
        // the host doesn't poison env reads, env-flag writes, or migrations
        // that depend on freshly-published config (e.g. permission.teams).
        $this->info('Clearing cached config/routes...');
        $this->callSilent('config:clear');
        $this->callSilent('route:clear');

        $this->info('Checking requirements...');

        $this->info('Checking user authentication...');
        if (! class_exists('App\Models\User') && ! class_exists('App\User')) {
            $this->error('Laravel CRM requires the user model, See https://laravel.com/docs/authentication');

            return;
        }

        $userClass = class_exists('App\Models\User') ? 'App\Models\User' : 'App\User';

        $this->info("Checking user authentication passed. (Using {$userClass})");

        // Patch the User model to add required CRM traits
        $this->patchUserModel($userClass);

        // Configure environment flags (teams, encryption) before migrations run
        $this->configureEnv();

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

        $this->call('vendor:publish', [
            '--provider' => 'VentureDrake\LaravelCrm\LaravelCrmServiceProvider',
            '--tag' => 'assets',
            '--force' => true,
        ]);

        $this->info('Publishing Flasher assets...');

        try {
            $this->call('flasher:install');
        } catch (\Throwable $e) {
            $this->warn('Could not publish Flasher assets: '.$e->getMessage());
            $this->warn('Run "php artisan flasher:install" manually if flash notifications are not working.');
        }

        $this->info('Composer dump autoload');
        $this->composer->dumpAutoloads();

        $this->info('Linking storage directory...');
        if (File::exists(public_path('storage'))) {
            $this->info('Storage symlink already exists. Skipping.');
        } else {
            try {
                $this->call('storage:link');
            } catch (\Throwable $e) {
                $this->warn('Could not create storage symlink: '.$e->getMessage());
                $this->warn('Run "php artisan storage:link" manually to enable file uploads.');
            }
        }

        $this->info('Setting up database...');
        $this->call('migrate');
        $this->callSilent('db:seed', [
            '--class' => 'VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder',
        ]);

        if ($userClass::where('crm_access', 1)->count() < 1) {
            $this->info('Create your default owner user');

            if ($this->option('owner-name')) {
                $name = trim($this->option('owner-name'));
            } else {
                $firstname = $this->ask('Whats your first name?');
                $lastname = $this->ask('Whats your last name?');
                $name = trim($firstname.' '.$lastname);
            }

            $email = $this->option('owner-email') ?? $this->ask('Whats your email address?');

            if ($this->option('owner-password')) {
                $password = $this->option('owner-password');
            } else {
                do {
                    $password = $this->secret('Enter a password (min 8 characters)');

                    if (strlen($password) < 8) {
                        $this->error('Password must be at least 8 characters. Please try again.');
                        $password = null;

                        continue;
                    }

                    $confirm = $this->secret('Confirm password');

                    if ($password !== $confirm) {
                        $this->error('Passwords do not match. Please try again.');
                        $password = null;
                    }
                } while (! $password);
            }

            if ($user = $userClass::where('email', $email)->first()) {
                $this->info('User already exists, granting crm access...');

                $user->update([
                    'crm_access' => 1,
                ]);

                $this->assignOwnerRole($user, $userClass);

                $this->info('User access and role updated.');
            } else {
                $user = $userClass::forceCreate([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'crm_access' => 1,
                ]);

                $this->assignOwnerRole($user, $userClass);

                $this->info('User created with owner role');
            }
        }

        $this->info('Laravel CRM is now installed.');

        $this->info('Clearing caches...');
        $this->callSilent('config:clear');
        $this->callSilent('route:clear');
        $this->callSilent('view:clear');
        $this->callSilent('cache:clear');

        if ($this->isInteractive() && $this->confirm('Would you like to show some love by starring the repo?')) {
            $url = 'https://github.com/venturedrake/laravel-crm';
            $exec = PHP_OS_FAMILY === 'Windows' ? 'start' : (PHP_OS_FAMILY === 'Darwin' ? 'open' : 'xdg-open');

            try {
                @exec("{$exec} {$url} 2>/dev/null", $output, $exitCode);

                if ($exitCode === 0) {
                    $this->line('Thanks for the love. ⭐');
                } else {
                    $this->line("Thanks! Visit {$url} to give us a ⭐");
                }
            } catch (\Throwable $e) {
                $this->line("Thanks! Visit {$url} to give us a ⭐");
            }
        }
    }

    /**
     * Checks if config exists given a filename.
     *
     * @param  string  $fileName
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

    /**
     * Patch the host application's User model to add required CRM traits.
     */
    private function patchUserModel(string $userClass): void
    {
        $relativePath = str_replace(['App\\', '\\'], ['', '/'], $userClass).'.php';
        $path = app_path($relativePath);

        if (! File::exists($path)) {
            $this->warn("⚠️  Could not locate User model at {$path}.");
            $this->warn('   You must manually add the following traits to your User model:');
            $this->warn('     use VentureDrake\LaravelCrm\Traits\HasCrmAccess;');
            $this->warn('     use VentureDrake\LaravelCrm\Traits\HasCrmTeams;');
            $this->warn('     use Spatie\Permission\Traits\HasRoles;');

            return;
        }

        $traits = [
            'VentureDrake\LaravelCrm\Traits\HasCrmAccess' => 'HasCrmAccess',
            'VentureDrake\LaravelCrm\Traits\HasCrmTeams' => 'HasCrmTeams',
            'Spatie\Permission\Traits\HasRoles' => 'HasRoles',
        ];

        $contents = File::get($path);
        $original = $contents;

        // Detect class short name (e.g. "User") to scope class-body edits
        if (! preg_match('/\bclass\s+(\w+)\b/', $contents, $classMatch)) {
            $this->warn("⚠️  Could not parse class declaration in {$path}. Patch the User model manually.");

            return;
        }
        $className = $classMatch[1];

        $missing = [];

        foreach ($traits as $fqcn => $shortName) {
            $hasImport = (bool) preg_match('/^use\s+'.preg_quote($fqcn, '/').'\s*;/m', $contents);
            $hasUseInClass = (bool) preg_match(
                '/class\s+'.preg_quote($className, '/').'\b[^{]*\{[^}]*?\buse\s+[^;]*\b'.preg_quote($shortName, '/').'\b[^;]*;/s',
                $contents
            );

            if ($hasImport && $hasUseInClass) {
                continue;
            }

            $missing[$fqcn] = $shortName;

            // 1. Add the `use Foo\Bar;` import after the namespace line if missing
            if (! $hasImport) {
                $contents = preg_replace(
                    '/^(namespace[^;]+;\s*\R)/m',
                    "$1\nuse {$fqcn};\n",
                    $contents,
                    1
                );
            }

            // 2. Insert the trait into the class body's `use Trait;` declaration
            if (! $hasUseInClass) {
                $classBodyPattern = '/(class\s+'.preg_quote($className, '/').'\b[^{]*\{\s*)/';

                if (preg_match('/(class\s+'.preg_quote($className, '/').'\b[^{]*\{\s*)(use\s+([^;]+);)/', $contents, $m)) {
                    // Append to existing `use TraitA, TraitB;`
                    $existing = trim($m[3]);
                    $newUse = 'use '.$existing.', '.$shortName.';';
                    $contents = str_replace($m[2], $newUse, $contents);
                } else {
                    // Insert a new `use Trait;` line as the first statement in the class body
                    $contents = preg_replace(
                        $classBodyPattern,
                        "$1    use {$shortName};\n\n    ",
                        $contents,
                        1
                    );
                }
            }
        }

        if (empty($missing)) {
            $this->info('User model already has the required CRM traits. Skipping.');

            return;
        }

        $this->info('The User model needs the following traits added: '.implode(', ', $missing));

        if (! $this->confirm("Patch {$path} automatically? (a backup will be created)", true)) {
            $this->warn('Skipped. You must add the traits to your User model manually before using the CRM.');

            return;
        }

        $backup = $path.'.backup-'.date('YmdHis');
        File::put($backup, $original);
        File::put($path, $contents);

        $this->info("✅ Patched {$path}");
        $this->line("   Backup written to {$backup}");
    }

    /**
     * Prompt the user for environment flags and persist them to .env.
     *
     * Must be called before migrations run, as LARAVEL_CRM_TEAMS controls
     * whether the Spatie permissions tables get team_id columns.
     */
    private function configureEnv(): void
    {
        $this->info('Configuring environment flags...');

        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->warn('No .env file found. Skipping environment configuration.');
            $this->warn('Set LARAVEL_CRM_TEAMS and LARAVEL_CRM_ENCRYPT_DB_FIELDS manually if needed.');

            return;
        }

        $changed = false;
        $envContents = File::get($envPath);

        // Multi-tenancy / teams
        if (! $this->envValueIsTruthy($envContents, 'LARAVEL_CRM_TEAMS')) {
            $enableTeams = $this->option('enable-teams') || (
                $this->isInteractive() && $this->confirm(
                    'Enable multi-tenancy (teams)? Each user will belong to one or more teams and data will be scoped per team.',
                    false
                )
            );

            if ($enableTeams) {
                $this->setEnvValue('LARAVEL_CRM_TEAMS', 'true');
                $this->info('  → LARAVEL_CRM_TEAMS=true');
                $changed = true;
            }
        } else {
            $this->info('  LARAVEL_CRM_TEAMS already enabled.');
        }

        // Encrypt sensitive DB fields
        if (! $this->envValueIsTruthy($envContents, 'LARAVEL_CRM_ENCRYPT_DB_FIELDS')) {
            $enableEncryption = $this->option('enable-encryption') || (
                $this->isInteractive() && $this->confirm(
                    'Encrypt sensitive database fields (names, emails, phones)? Recommended for production. '.
                    'Note: requires APP_KEY to remain stable; rotating the key without re-encrypting will lose data.',
                    false
                )
            );

            if ($enableEncryption) {
                $this->setEnvValue('LARAVEL_CRM_ENCRYPT_DB_FIELDS', 'true');
                $this->info('  → LARAVEL_CRM_ENCRYPT_DB_FIELDS=true');
                $changed = true;
            }
        } else {
            $this->info('  LARAVEL_CRM_ENCRYPT_DB_FIELDS already enabled.');
        }

        if ($changed) {
            // Reload env values for the running process so subsequent migrations
            // and seeders pick them up.
            $this->callSilent('config:clear');
        }
    }

    /**
     * Check if a given key in the raw .env contents is set to a truthy value.
     */
    private function envValueIsTruthy(string $envContents, string $key): bool
    {
        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $envContents, $m)) {
            return false;
        }

        $value = strtolower(trim($m[1], " \t\"'"));

        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Write or replace a key/value pair in the .env file.
     */
    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $contents = File::get($envPath);
        $line = "{$key}={$value}";
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $line, $contents);
        } else {
            $contents = rtrim($contents, "\r\n").PHP_EOL.$line.PHP_EOL;
        }

        File::put($envPath, $contents);

        // Update the in-process env so config() re-reads pick up the new value
        // after the subsequent config:clear call.
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Assign the Owner role via raw DB inserts.
     *
     * The User class is autoloaded by the ServiceProvider before this installer
     * runs, so the in-memory class still has the *unpatched* definition — i.e.
     * no HasRoles trait. Calling $user->assignRole() would fatal on a fresh
     * install. Using direct DB queries works regardless of in-memory class state;
     * the trait will be available normally on the next process boot.
     */
    private function assignOwnerRole(object $user, string $userClass): void
    {
        $roleTable = config('permission.table_names.roles', 'roles');
        $modelHasRoles = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');
        $teamFk = config('permission.column_names.team_foreign_key', 'team_id');
        $teamsEnabled = (bool) config('permission.teams', false);

        $roleQuery = DB::table($roleTable)
            ->where('name', 'Owner')
            ->where('crm_role', 1);

        if (Schema::hasColumn($roleTable, 'team_id')) {
            $roleQuery->whereNull('team_id');
        }

        $role = $roleQuery->first();

        if (! $role) {
            $this->error('Owner role not found. The seeder may not have run correctly.');
            $this->error('Re-run: php artisan db:seed --class=VentureDrake\\LaravelCrm\\Database\\Seeders\\LaravelCrmTablesSeeder');

            return;
        }

        $query = DB::table($modelHasRoles)
            ->where('role_id', $role->id)
            ->where($morphKey, $user->getKey())
            ->where('model_type', $userClass);

        if ($teamsEnabled && Schema::hasColumn($modelHasRoles, $teamFk)) {
            $query->whereNull($teamFk);
        }

        if ($query->exists()) {
            $this->info('Owner role already assigned.');

            return;
        }

        $row = [
            'role_id' => $role->id,
            $morphKey => $user->getKey(),
            'model_type' => $userClass,
        ];

        if ($teamsEnabled && Schema::hasColumn($modelHasRoles, $teamFk)) {
            $row[$teamFk] = null;
        }

        DB::table($modelHasRoles)->insert($row);

        $this->info('Owner role assigned.');
    }

    /**
     * Whether the command is running interactively (i.e. not with --no-interaction).
     */
    private function isInteractive(): bool
    {
        return $this->input->isInteractive();
    }
}
