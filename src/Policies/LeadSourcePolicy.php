<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\LeadSource;

class LeadSourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm lead sources')) {
            return true;
        }
    }

    public function view(User $user, LeadSource $leadSource)
    {
        if ($user->hasPermissionTo('view crm lead sources')) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm lead sources')) {
            return true;
        }
    }

    public function update(User $user, LeadSource $leadSource)
    {
        if ($user->hasPermissionTo('edit crm lead sources')) {
            return true;
        }
    }

    public function delete(User $user, LeadSource $leadSource)
    {
        if ($user->hasPermissionTo('delete crm lead sources')) {
            return true;
        }
    }

    public function restore(User $user, LeadSource $leadSource)
    {
        if ($user->hasPermissionTo('delete crm lead sources')) {
            return true;
        }
    }

    public function forceDelete(User $user, LeadSource $leadSource)
    {
        //
    }
}
