<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class FeatureView extends Model
{
    use BelongsToTeams;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'feature_views';
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
