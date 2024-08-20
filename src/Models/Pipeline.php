<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Pipeline extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'pipelines';
    }

    public function pipelineStages()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\PipelineStage::class);
    }

    public function leads()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Lead::class);
    }

    public function deals()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Deal::class);
    }
}
