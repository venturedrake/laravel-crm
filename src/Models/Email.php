<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Email extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_emails';

    protected $guarded = ['id'];

    protected $encryptable = [
        'address',
    ];

    /**
     * Get all of the owning emailable models.
     */
    public function emailable()
    {
        return $this->morphTo();
    }
}
