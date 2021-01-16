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
        return config('laravel-crm.db_table_prefix').parent::getTable();
    }
}
