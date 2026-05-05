<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class EmailTemplate extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'email_templates';
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }
}
