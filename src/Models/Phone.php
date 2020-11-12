<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Phone extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_phones';

    protected $guarded = ['id','external_id'];

    protected $encryptable = [
        'number',
    ];

    /**
     * Get all of the owning phoneable models.
     */
    public function phoneable()
    {
        return $this->morphTo();
    }
}
