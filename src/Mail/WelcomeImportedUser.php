<?php

namespace VentureDrake\LaravelCrm\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeImportedUser extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $recipientEmail,
        public readonly string $setPasswordUrl,
    ) {}

    /**
     * Build the message.
     */
    public function build(): static
    {
        return $this
            ->subject(ucfirst(__('laravel-crm::lang.welcome_email_subject', ['app' => config('app.name')])))
            ->to($this->recipientEmail, $this->name)
            ->markdown('laravel-crm::mail.welcome-imported-user');
    }
}
