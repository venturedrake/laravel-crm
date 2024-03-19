<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class Field extends Model
{
    use SoftDeletes;
    use BelongsToTeams;

    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'fields';
    }

    public function fieldGroup()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\FieldGroup::class);
    }

    public function fieldOptions()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\FieldOption::class);
    }
}
