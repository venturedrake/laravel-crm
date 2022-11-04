<?php

namespace VentureDrake\LaravelCrm\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Task extends Model
{
    use SoftDeletes;
    use BelongsToTeams;
    
    protected $guarded = ['id'];
    

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'tasks';
    }
    
    /**
     * Get all of the owning taskable models.
     */
    public function taskable()
    {
        return $this->morphTo('taskable');
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
}
