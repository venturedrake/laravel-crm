<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Phone extends Model
{
    
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_phones';

    protected $guarded = [];

    protected $encryptable = [
        'number',
    ];
    
}
