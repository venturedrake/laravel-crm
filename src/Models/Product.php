<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Product extends Model
{
    use SoftDeletes;
    use BelongsToTeams;
    use HasCrmFields;
    use SearchFilters;

    protected $guarded = ['id'];

    protected $searchable = [
        'name',
    ];

    protected $filterable = [
        'user_owner_id',
        'labels.id',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'products';
    }

    public function productPrices()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\ProductPrice::class);
    }

    public function getDefaultPrice()
    {
        return $this->productPrices()->where('currency', Setting::currency()->value ?? 'USD')->first();
    }

    public function productVariations()
    {
        return $this->hasMany(\VentureDrake\LaravelCrm\Models\ProductVariation::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\ProductCategory::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(\App\User::class, 'user_restored_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(\App\User::class, 'user_owner_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Get the xero item associated with the product.
     */
    public function xeroItem()
    {
        return $this->hasOne(\VentureDrake\LaravelCrm\Models\XeroItem::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(\VentureDrake\LaravelCrm\Models\TaxRate::class);
    }
}
