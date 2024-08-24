<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class PipelineStageProbability extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'pipeline_stage_probabilities';
    }

    public function pipelineStage()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\PipelineStage::class);
    }
}
