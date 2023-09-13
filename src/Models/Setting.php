<?php

namespace VentureDrake\LaravelCrm\Models;

use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmAddresses;
use VentureDrake\LaravelCrm\Traits\HasCrmEmails;
use VentureDrake\LaravelCrm\Traits\HasCrmPhones;

class Setting extends Model
{
    use BelongsToTeams;
    use HasCrmPhones;
    use HasCrmEmails;
    use HasCrmAddresses;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if ($model->global) {
                switch ($model->name) {
                    case "app_name":
                    case "app_env":
                    case "app_url":
                    case "version":
                    case "install_id":
                    case "version_latest":
                        $model->global = 1;

                        break;
                }
            }
        });
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'settings';
    }

    public function scopeCurrency($query)
    {
        return $query->where('name', 'currency')->first();
    }

    public function scopeCountry($query)
    {
        return $query->where('name', 'country')->first();
    }
}
