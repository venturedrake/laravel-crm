<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;

class Monitor extends Model
{
    use BelongsToTeams;
    use HasCrmFields;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_status_changed_at' => 'datetime',
        'down_since_at' => 'datetime',
        'notified_at' => 'datetime',
        'ssl_notified_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'monitors';
    }

    public function checks()
    {
        return $this->hasMany(MonitorCheck::class)->orderBy('checked_at', 'desc');
    }

    public function customFieldValues()
    {
        return $this->morphMany(FieldValue::class, 'custom_field_valueable');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
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
}
