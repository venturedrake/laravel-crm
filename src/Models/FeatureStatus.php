<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class FeatureStatus extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'feature_statuses';
    }

    public function getColorAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return str_starts_with($value, '#') ? $value : '#'.ltrim($value, '#');
    }

    public function features()
    {
        return $this->hasMany(Feature::class, 'feature_status_id');
    }
}
