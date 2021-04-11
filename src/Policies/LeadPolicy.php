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
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the lead.
     *
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return mixed
     */
    public function view(User $user, Lead $lead)
    {
        return true;
    }

    /**
     * Determine whether the user can create leads.
     *
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the lead.
     *
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return mixed
     */
    public function update(User $user, Lead $lead)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the lead.
     *
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return mixed
     */
    public function delete(User $user, Lead $lead)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the lead.
     *
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return mixed
     */
    public function restore(User $user, Lead $lead)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the lead.
     *
     * @param  \VentureDrake\LaravelCrm\Models\User  $user
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return mixed
     */
    public function forceDelete(User $user, Lead $lead)
    {
        return false;
    }
}
