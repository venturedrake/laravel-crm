<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Organisation extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;

    protected $guarded = ['id'];

    protected $encryptable = [
        'name',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'organisations';
    }

    public function people()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\Person::class);
    }

    public function addresses()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Address::class, 'addressable');
    }

    public function getPrimaryAddress()
    {
        return $this->addresses()->where('primary', 1)->first();
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

    public function ownerUser()
    {
        return $this->belongsTo(\App\User::class, 'user_owner_id');
    }
}
