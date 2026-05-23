<?php

namespace VentureDrake\LaravelCrm\Models;

class MonitorCheck extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'checked_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'monitor_checks';
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
