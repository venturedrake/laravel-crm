<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\SmsTemplate;

class SmsTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm sms-templates')) {
            return true;
        }
    }

    public function view(User $user, SmsTemplate $template)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm sms-templates')) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm sms-templates')) {
            return true;
        }
    }

    public function update(User $user, SmsTemplate $template)
    {
        if ($template->is_system) {
            return false;
        }

        if ($this->isEnabled() && $user->hasPermissionTo('edit crm sms-templates')) {
            return true;
        }
    }

    public function delete(User $user, SmsTemplate $template)
    {
        if ($template->is_system) {
            return false;
        }

        if ($this->isEnabled() && $user->hasPermissionTo('delete crm sms-templates')) {
            return true;
        }
    }

    public function restore(User $user, SmsTemplate $template)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm sms-templates')) {
            return true;
        }
    }

    public function forceDelete(User $user, SmsTemplate $template)
    {
        return false;
    }

    protected function isEnabled()
    {
        if (is_array(config('laravel-crm.modules')) && in_array('sms-marketing', config('laravel-crm.modules'))) {
            return true;
        } elseif (! config('laravel-crm.modules')) {
            return true;
        }
    }
}
