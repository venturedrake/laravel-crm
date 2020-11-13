<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Organisation extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_organisations';

    protected $guarded = ['id'];

    protected $encryptable = [
        'name',
    ];
}
