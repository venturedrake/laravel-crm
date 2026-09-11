<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Product extends Model
{
    use BelongsToTeams;
    use HasCrmFields;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $searchable = [
        'name',
    ];

    protected $filterable = [
        'user_owner_id',
        'labels.id',
    ];

    protected $casts = [
        'active' => 'boolean',
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
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * The price row for the install's currency.
     *
     * Reads the currency off the cached settings map rather than re-querying
     * crm_settings, and prefers an eager-loaded productPrices — the products
     * index calls this three times for every row it renders.
     */
    public function getDefaultPrice()
    {
        $currency = app('laravel-crm.settings')->get('currency') ?? 'USD';

        if ($this->relationLoaded('productPrices')) {
            return $this->productPrices->firstWhere('currency', $currency);
        }

        return $this->productPrices()->where('currency', $currency)->first();
    }

    public function productVariations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(User::class, 'user_restored_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
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
        return $this->hasOne(XeroItem::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
}
