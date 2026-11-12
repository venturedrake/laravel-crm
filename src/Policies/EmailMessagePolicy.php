<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\EmailMessage;

class EmailMessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any email messages.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm emails')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the email message.
     *
     * @return mixed
     */
    public function view(User $user, EmailMessage $emailMessage)
    {
        if ($user->hasPermissionTo('view crm emails')) {
            return true;
        }
    }

    /**
     * Determine whether the user can log email messages.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm emails')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the email message.
     *
     * @return mixed
     */
    public function update(User $user, EmailMessage $emailMessage)
    {
        if ($user->hasPermissionTo('edit crm emails')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the email message.
     *
     * @return mixed
     */
    public function delete(User $user, EmailMessage $emailMessage)
    {
        if ($user->hasPermissionTo('delete crm emails')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the email message.
     *
     * @return mixed
     */
    public function restore(User $user, EmailMessage $emailMessage)
    {
        if ($user->hasPermissionTo('delete crm emails')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the email message.
     *
     * @return mixed
     */
    public function forceDelete(User $user, EmailMessage $emailMessage)
    {
        return false;
    }
}
