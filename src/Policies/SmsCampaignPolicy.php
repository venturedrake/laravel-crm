<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\SmsCampaign;

class SmsCampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm sms-campaigns')) {
            return true;
        }
    }

    public function view(User $user, SmsCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm sms-campaigns')) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm sms-campaigns')) {
            return true;
        }
    }

    public function update(User $user, SmsCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm sms-campaigns')) {
            return true;
        }
    }

    public function delete(User $user, SmsCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm sms-campaigns')) {
            return true;
        }
    }

    public function restore(User $user, SmsCampaign $campaign)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm sms-campaigns')) {
            return true;
        }
    }

    public function forceDelete(User $user, SmsCampaign $campaign)
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
