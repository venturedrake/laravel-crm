<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;
    
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'labels';
    }

    /**
     * Get all of the owning noteable models.
     */
    public function noteable()
    {
        return $this->morphTo();
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
}
