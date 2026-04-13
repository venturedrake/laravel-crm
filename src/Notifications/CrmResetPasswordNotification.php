<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CrmResetPasswordNotification extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('laravel-crm.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(ucfirst(trans('laravel-crm::lang.reset_password_notification')))
            ->line(ucfirst(trans('laravel-crm::lang.reset_password_email_line_1')))
            ->action(ucfirst(trans('laravel-crm::lang.reset_password')), $url)
            ->line(ucfirst(trans('laravel-crm::lang.reset_password_email_line_2')));
    }
}
