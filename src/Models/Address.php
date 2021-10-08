<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Address extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    use BelongsToTeams;

    protected $guarded = ['id'];

    protected $encryptable = [
        'address',
        'name',
        'line1',
        'line2',
        'line3',
        'code',
        'city',
        'state',
        'country',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'addresses';
    }

    /**
     * Get all of the owning addressable models.
     */
    public function addressable()
    {
        return $this->morphTo();
    }

    public function addressType()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\AddressType::class);
    }
}
