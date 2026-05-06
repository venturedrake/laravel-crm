<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasEncryptableFields;

class Phone extends Model
{
    use BelongsToTeams;
    use HasEncryptableFields;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $encryptable = [
        'number',
    ];

    protected $casts = [
        'primary' => 'boolean',
        'subscribed' => 'boolean',
        'unsubscribed_at' => 'datetime',
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

    public function markUnsubscribed(): void
    {
        $this->update([
            'subscribed' => false,
            'unsubscribed_at' => now(),
        ]);
    }
}
