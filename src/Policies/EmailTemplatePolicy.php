<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\EmailTemplate;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm email-templates')) {
            return true;
        }
    }

    public function view(User $user, EmailTemplate $template)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm email-templates')) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm email-templates')) {
            return true;
        }
    }

    public function update(User $user, EmailTemplate $template)
    {
        if ($template->is_system) {
            return false;
        }

        if ($this->isEnabled() && $user->hasPermissionTo('edit crm email-templates')) {
            return true;
        }
    }

    public function delete(User $user, EmailTemplate $template)
    {
        if ($template->is_system) {
            return false;
        }

        if ($this->isEnabled() && $user->hasPermissionTo('delete crm email-templates')) {
            return true;
        }
    }

    public function restore(User $user, EmailTemplate $template)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm email-templates')) {
            return true;
        }
    }

    public function forceDelete(User $user, EmailTemplate $template)
    {
        return false;
    }

    protected function isEnabled()
    {
        if (is_array(config('laravel-crm.modules')) && in_array('email-marketing', config('laravel-crm.modules'))) {
            return true;
        } elseif (! config('laravel-crm.modules')) {
            return true;
        }
    }
}
