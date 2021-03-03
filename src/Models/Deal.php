<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\HasCustomFields;

class Deal extends Model
{
    use SoftDeletes;
    use HasCustomFields;

    protected $guarded = ['id'];
    
    protected $casts = [
        'expected_close' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'deals';
    }
}
