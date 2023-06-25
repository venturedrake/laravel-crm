<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Lead;

class LeadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any leads.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm leads')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the lead.
     *
     * @param  \App\User  $user
     * @param  \App\Lead  $lead
     * @return mixed
     */
    public function view(User $user, Lead $lead)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('view crm leads')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create leads.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('create crm leads')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the lead.
     *
     * @param  \App\User  $user
     * @param  \App\Lead  $lead
     * @return mixed
     */
    public function update(User $user, Lead $lead)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('edit crm leads')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the lead.
     *
     * @param  \App\User  $user
     * @param  \App\Lead  $lead
     * @return mixed
     */
    public function delete(User $user, Lead $lead)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm leads')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the lead.
     *
     * @param  \App\User  $user
     * @param  \App\Lead  $lead
     * @return mixed
     */
    public function restore(User $user, Lead $lead)
    {
        if ($this->isEnabled() && $user->hasPermissionTo('delete crm leads')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the lead.
     *
     * @param  \App\User  $user
     * @param  \App\Lead  $lead
     * @return mixed
     */
    public function forceDelete(User $user, Lead $lead)
    {
        return false;
    }

    protected function isEnabled()
    {
        if(is_array(config('laravel-crm.modules')) && in_array('leads', config('laravel-crm.modules'))) {
            return true;
        } elseif(! config('laravel-crm.modules')) {
            return true;
        }
    }
}
