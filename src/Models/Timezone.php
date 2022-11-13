<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timezone extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'timezones';
    }
}
