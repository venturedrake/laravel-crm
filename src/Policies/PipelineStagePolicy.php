<?php

namespace VentureDrake\LaravelCrm\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class PipelineStagePolicy
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
     * @param  \App\PipelineStage  $pipelineStage
     * @return mixed
     */
    public function view(User $user, PipelineStage $pipelineStage)
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
     * @param  \App\PipelineStage  $pipelineStage
     * @return mixed
     */
    public function update(User $user, PipelineStage $pipelineStage)
    {
        if ($user->hasPermissionTo('edit crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\PipelineStage  $pipelineStage
     * @return mixed
     */
    public function delete(User $user, PipelineStage $pipelineStage)
    {
        if ($user->hasPermissionTo('delete crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\PipelineStage  $pipelineStage
     * @return mixed
     */
    public function restore(User $user, PipelineStage $pipelineStage)
    {
        if ($user->hasPermissionTo('delete crm pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the pipeline.
     *
     * @param  \App\User  $user
     * @param  \App\PipelineStage  $pipelineStage
     * @return mixed
     */
    public function forceDelete(User $user, PipelineStage $pipelineStage)
    {
        return false;
    }
}
