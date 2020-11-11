<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Address extends Model
{
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_addresses';

    protected $guarded = ['id','external_id'];

    protected $encryptable = [
        'address',
        'line1',
        'line2',
        'line3',
        'code',
        'city',
        'state',
        'country',
    ];

    /**
     * Get all of the owning addressable models.
     */
    public function addressable()
    {
        return $this->morphTo();
    }
    
}
