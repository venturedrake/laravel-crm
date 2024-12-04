<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Note;

class NotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any notes.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm notes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the note.
     *
     * @param  \App\Note  $note
     * @return mixed
     */
    public function view(User $user, Note $note)
    {
        if ($user->hasPermissionTo('view crm notes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create notes.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm notes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the note.
     *
     * @param  \App\Note  $note
     * @return mixed
     */
    public function update(User $user, Note $note)
    {
        if ($user->hasPermissionTo('edit crm notes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the note.
     *
     * @param  \App\Note  $note
     * @return mixed
     */
    public function delete(User $user, Note $note)
    {
        if ($user->hasPermissionTo('delete crm notes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the note.
     *
     * @param  \App\Note  $note
     * @return mixed
     */
    public function restore(User $user, Note $note)
    {
        if ($user->hasPermissionTo('delete crm notes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the note.
     *
     * @param  \App\Note  $note
     * @return mixed
     */
    public function forceDelete(User $user, Note $note)
    {
        return false;
    }
}
