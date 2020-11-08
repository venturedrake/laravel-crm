<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Organisation extends Model
{
    
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_organisations';

    protected $guarded = [];

    protected $encryptable = [
        'name',
    ];
    
}
