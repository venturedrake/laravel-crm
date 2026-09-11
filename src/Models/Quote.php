<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Quote extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use HasGlobalSettings;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_at' => 'datetime',
        'expire_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected $searchable = [
        'quote_id',
        'title',
        'reference',
        'quote_id',
        'person.first_name',
        'person.middle_name',
        'person.last_name',
        'person.maiden_name',
        'organization.name',
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
        return config('laravel-crm.db_table_prefix').'quotes';
    }

    public function getQuoteIdAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            // Off the memoised settings map rather than a fresh
            // crm_settings query. This accessor runs for every row of an index
            // page and for every document a mail template renders.
            return app('laravel-crm.settings')->get('quote_prefix').$this->number;
        }
    }

    public function getNumberAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            return $this->id;
        }
    }

    public function setSubtotalAttribute($value)
    {
        $this->attributes['subtotal'] = Money::toInteger($value);
    }

    public function setDiscountAttribute($value)
    {
        $this->attributes['discount'] = Money::toInteger($value);
    }

    public function setTaxAttribute($value)
    {
        $this->attributes['tax'] = Money::toInteger($value);
    }

    public function setAdjustmentsAttribute($value)
    {
        $this->attributes['adjustments'] = Money::toInteger($value);
    }

    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = Money::toInteger($value);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quoteProducts()
    {
        return $this->hasMany(QuoteProduct::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get all of the lead's custom field values.
     */
    public function customFieldValues()
    {
        return $this->morphMany(FieldValue::class, 'custom_field_valueable');
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

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
    }

    /**
     * Get all of the labels for the lead.
     */
    public function labels()
    {
        return $this->morphToMany(Label::class, config('laravel-crm.db_table_prefix').'labelable');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderComplete()
    {
        foreach ($this->quoteProducts as $quoteProduct) {
            $quantity = Quantity::toFloat($quoteProduct->quantity);

            foreach ($this->orders as $order) {
                // Through the collection rather than the query builder, so a
                // caller that eager loaded orders.orderProducts pays nothing
                // here. Uneager-loaded this is still one query per order rather
                // than one per order per line.
                if ($orderProduct = $order->orderProducts->firstWhere('quote_product_id', $quoteProduct->id)) {
                    $quantity -= Quantity::toFloat($orderProduct->quantity);
                }
            }

            // Not `> 0`: subtracting decimal quantities leaves binary float
            // dust (1.1 less 0.7 less 0.4 is 1.11e-16), which would leave the
            // quote showing as not fully ordered forever.
            if (Quantity::isPositive($quantity)) {
                return false;
            }
        }

        return true;
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }
}
