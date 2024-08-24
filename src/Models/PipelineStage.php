<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class PipelineStage extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'pipeline_stages';
    }

    public function pipeline()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Pipeline::class);
    }

    public function pipelineStageProbability()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\PipelineStageProbability::class);
    }

    public function leads()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Lead::class);
    }

    public function deals()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Deal::class);
    }

    public function quotes()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Quote::class);
    }
}
