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

        $normalized = '#'.ltrim((string) $value, '#');

        // Reject anything that isn't a strict hex literal so the value can be
        // safely interpolated into a `style="background-color: …"` attribute
        // without enabling CSS injection.
        if (! preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $normalized)) {
            return null;
        }

        return $normalized;
    }

    public function features()
    {
        return $this->hasMany(Feature::class, 'feature_status_id');
    }
}
