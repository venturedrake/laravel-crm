<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Email extends Model
{
    
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_emails';

    protected $guarded = [];

    protected $encryptable = [
        'address',
    ];
    
}
