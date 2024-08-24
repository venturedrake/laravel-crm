<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class PipelineStageObserver
{
    /**
     * Handle the pipelineStage "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function creating(PipelineStage $pipelineStage)
    {
        $pipelineStage->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the pipelineStage "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function created(PipelineStage $pipelineStage)
    {
        //
    }

    /**
     * Handle the pipelineStage "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function updating(PipelineStage $pipelineStage)
    {
        //
    }

    /**
     * Handle the pipelineStage "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function updated(PipelineStage $pipelineStage)
    {
        //
    }

    /**
     * Handle the pipelineStage "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function deleting(PipelineStage $pipelineStage)
    {
        //
    }

    /**
     * Handle the pipelineStage "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function deleted(PipelineStage $pipelineStage)
    {
        //
    }

    /**
     * Handle the pipelineStage "restoring" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function restoring(PipelineStage $pipelineStage)
    {
    }

    /**
     * Handle the pipelineStage "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function restored(PipelineStage $pipelineStage)
    {
        //
    }

    /**
     * Handle the pipelineStage "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\PipelineStage  $pipelineStage
     * @return void
     */
    public function forceDeleted(PipelineStage $pipelineStage)
    {
        //
    }
}
