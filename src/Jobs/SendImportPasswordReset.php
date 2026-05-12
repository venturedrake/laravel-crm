<?php

namespace VentureDrake\LaravelCrm\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use VentureDrake\LaravelCrm\Mail\WelcomeImportedUser;

class SendImportPasswordReset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly string $email) {}

    public function handle(): void
    {
        $user = User::where('email', $this->email)->first();

        if (! $user) {
            return;
        }

        $token = Password::createToken($user);

        $setPasswordUrl = route('laravel-crm.password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);

        Mail::send(new WelcomeImportedUser(
            name: $user->name,
            recipientEmail: $user->email,
            setPasswordUrl: $setPasswordUrl,
        ));
    }
}
