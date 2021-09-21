<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Email extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    use BelongsToTeams;

    protected $guarded = ['id'];

    protected $encryptable = [
        'address',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'emails';
    }

    /**
     * Get all of the owning emailable models.
     */
    public function emailable()
    {
        return $this->morphTo();
    }
}
