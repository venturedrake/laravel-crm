<?php

namespace VentureDrake\LaravelCrm\Console\Commands;

use Illuminate\Console\Command;

class IssueApiToken extends Command
{
    protected $signature = 'laravel-crm:api-token {email : The email of the user to issue a token for} {--name= : A human-readable name for the token}';

    protected $description = 'Issue a Sanctum API token for a CRM user.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name') ?: 'api-token';

        $userClass = config('auth.providers.users.model');

        if (! $userClass || ! class_exists($userClass)) {
            $this->error('Could not resolve the application user model from auth.providers.users.model.');

            return 1;
        }

        $user = $userClass::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}].");

            return 1;
        }

        if (! (bool) ($user->crm_access ?? false)) {
            $this->error("User [{$email}] does not have CRM access.");

            return 1;
        }

        $token = $user->createToken($name);

        $this->info($token->plainTextToken);

        return 0;
    }
}
