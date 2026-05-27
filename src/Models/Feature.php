<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Feature extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_public' => 'boolean',
        'votes_count' => 'integer',
        'comments_count' => 'integer',
        'views_count' => 'integer',
    ];

    protected $searchable = [
        'feature_id',
        'title',
        'description',
    ];

    protected $filterable = [
        'feature_status_id',
        'submitted_by_user_id',
        'user_owner_id',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'features';
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function status()
    {
        return $this->belongsTo(FeatureStatus::class, 'feature_status_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function comments()
    {
        return $this->hasMany(FeatureComment::class, 'feature_id');
    }

    public function views()
    {
        return $this->hasMany(FeatureView::class, 'feature_id');
    }

    public function voters()
    {
        return $this->belongsToMany(
            User::class,
            config('laravel-crm.db_table_prefix').'feature_votes',
            'feature_id',
            'user_id'
        )->using(FeatureVote::class)->withPivot('team_id')->withTimestamps();
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(User::class, 'user_restored_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
    }

    public function customFieldValues()
    {
        return $this->morphMany(FieldValue::class, 'custom_field_valueable');
    }
}
