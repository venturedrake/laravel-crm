<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FeatureVote extends Pivot
{
    public $incrementing = true;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'feature_votes';
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
