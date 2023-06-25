<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasCrmUserRelations;
use VentureDrake\LaravelCrm\Traits\SearchFilters;
use VentureDrake\LaravelEncryptable\Traits\LaravelEncryptableTrait;

class Client extends Model
{
    use SoftDeletes;
    use LaravelEncryptableTrait;
    use BelongsToTeams;
    use HasCrmFields;
    use SearchFilters;
    use Sortable;
    use HasCrmActivities;
    use HasCrmUserRelations;

    protected $guarded = ['id'];

    protected $encryptable = [
        'name',
    ];

    protected $searchable = [
        'name',
    ];

    protected $filterable = [
        'user_owner_id',
        'labels.id',
    ];

    public $sortable = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'clients';
    }

    public function getNameAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            return $this->clientable->name ?? null;
        }
    }

    /**
     * Get all of the owning clientable models.
     */
    public function clientable()
    {
        return $this->morphTo();
    }

    /**
     * Get all of the labels for the person.
     */
    public function labels()
    {
        return $this->morphToMany(\VentureDrake\LaravelCrm\Models\Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }

    public function contacts()
    {
        return $this->morphMany(\VentureDrake\LaravelCrm\Models\Contact::class, 'contactable');
    }
}
