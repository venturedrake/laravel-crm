<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\EmailCampaign;

class EmailCampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm email-campaigns')) {
            return true;
        }
    }

    public function view(User $user, EmailCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm email-campaigns')) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm email-campaigns')) {
            return true;
        }
    }

    public function update(User $user, EmailCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm email-campaigns')) {
            return true;
        }
    }

    public function delete(User $user, EmailCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm email-campaigns')) {
            return true;
        }
    }

    public function restore(User $user, EmailCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm email-campaigns')) {
            return true;
        }
    }

    public function forceDelete(User $user, EmailCampaign $campaign)
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
