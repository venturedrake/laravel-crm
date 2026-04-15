<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use VentureDrake\LaravelCrm\Models\Role;

class LaravelCrmAddUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:add-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or grant CRM access to a user';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('**************************************************************************');
        $this->info('*                    ADD A LARAVEL CRM USER                              *');
        $this->info('**************************************************************************');

        $userClass = class_exists('App\Models\User') ? 'App\Models\User' : 'App\User';

        if (! class_exists($userClass)) {
            $this->error('Laravel CRM requires the user model. See https://laravel.com/docs/authentication');

            return 1;
        }

        $roles = Role::where('crm_role', 1)->pluck('name')->toArray();

        if (empty($roles)) {
            $this->error('No CRM roles found. Please run php artisan laravelcrm:permissions first.');

            return 1;
        }

        $email = $this->ask('Enter the user\'s email address');

        $user = $userClass::where('email', $email)->first();

        if ($user) {
            $this->info("User found: {$user->name} ({$user->email})");

            if (! $this->confirm('Grant this user CRM access?', true)) {
                $this->info('Aborted. No changes were made.');

                return 0;
            }

            $user->forceFill(['crm_access' => 1])->save();

            $this->info('CRM access granted.');
        } else {
            $this->info('No existing user found with that email. Creating a new user...');

            $firstname = $this->ask('First name');
            $lastname = $this->ask('Last name');
            $password = $this->secret('Password');

            if (! $password) {
                $this->error('A password is required.');

                return 1;
            }

            $user = $userClass::forceCreate([
                'name' => trim($firstname.' '.$lastname),
                'email' => $email,
                'password' => Hash::make($password),
                'crm_access' => 1,
            ]);

            $this->info("User created: {$user->name} ({$user->email})");
        }

        $selectedRole = $this->choice('Assign a CRM role', $roles, array_search('Employee', $roles) ?: 0);

        if ($existingCrmRole = $user->roles()->where('crm_role', 1)->first()) {
            $user->removeRole($existingCrmRole);
        }

        if ($role = Role::where('name', $selectedRole)->where('crm_role', 1)->first()) {
            $user->assignRole($role);
            $this->info("Role '{$selectedRole}' assigned.");
        }

        $this->info('✅ Done! The user now has CRM access.');

        return 0;
    }
}

