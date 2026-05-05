<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasEncryptableFields;

class Email extends Model
{
    use BelongsToTeams;
    use HasEncryptableFields;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $encryptable = [
        'address',
    ];

    protected $casts = [
        'primary' => 'boolean',
        'subscribed' => 'boolean',
        'unsubscribed_at' => 'datetime',
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

    public function markUnsubscribed(): void
    {
        $this->update([
            'subscribed' => false,
            'unsubscribed_at' => now(),
        ]);
    }
}
