<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class OrganisationType extends Model
{
    use BelongsToTeams;
    use SoftDeletes;
    use Sortable;

    protected $guarded = ['id'];

    public $sortable = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'organisation_types';
    }

    public function organisations()
    {
        return $this->belongsToMany(Organization::class);
    }
}
