<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Phone extends Model
{
    use BelongsToTeams;
    use LaravelEncryptableTrait;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $encryptable = [
        'number',
    ];

    protected $casts = [
        'primary' => 'boolean',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'phones';
    }

    /**
     * Get all of the owning phoneable models.
     */
    public function phoneable()
    {
        return $this->morphTo();
    }
}
