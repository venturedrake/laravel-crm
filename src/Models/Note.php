<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;

class Note extends Model
{
    use SoftDeletes;
    use BelongsToTeams;
    use HasGlobalSettings;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'noted_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'notes';
    }

    public function setNotedAtAttribute($value)
    {
        if ($value) {
            $this->attributes['noted_at'] = Carbon::createFromFormat($this->dateFormat().' H:i', $value);
        }
    }

    /**
     * Get all of the owning noteable models.
     */
    public function noteable()
    {
        return $this->morphTo('noteable');
    }

    public function createdByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_restored_id');
    }

    public function relatedNote()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Note::class, 'related_note_id');
    }

    public function activity()
    {
        return $this->morphOne(\VentureDrake\LaravelCrm\Models\Activity::class, 'recordable');
    }
}
