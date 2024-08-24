<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\Pipeline;

class PipelinePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any pipelines.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\Pipeline  $pipeline
     * @return mixed
     */
    public function view(User $user, Pipeline $pipeline)
    {
        if ($user->hasPermissionTo('view crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create pipelines.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermissionTo('create crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\Pipeline  $pipeline
     * @return mixed
     */
    public function update(User $user, Pipeline $pipeline)
    {
        if ($user->hasPermissionTo('edit crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\Pipeline  $pipeline
     * @return mixed
     */
    public function delete(User $user, Pipeline $pipeline)
    {
        if ($user->hasPermissionTo('delete crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\Pipeline  $pipeline
     * @return mixed
     */
    public function restore(User $user, Pipeline $pipeline)
    {
        if ($user->hasPermissionTo('delete crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\Pipeline  $pipeline
     * @return mixed
     */
    public function forceDelete(User $user, Pipeline $pipeline)
    {
        return false;
    }
}
