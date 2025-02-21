<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class XeroContact extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'xero_contacts';
    }

    /**
     * Get the organization that owns the xero contact.
     */
    public function organization()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\Organization::class);
    }
}
