<?php

namespace VentureDrake\LaravelCrm\Traits;

use VentureDrake\LaravelCrm\Notifications\CrmResetPasswordNotification;

trait SendsCrmPasswordReset
{
    /**
     * Send the password reset notification using the CRM's own reset URL.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CrmResetPasswordNotification($token));
    }
}
