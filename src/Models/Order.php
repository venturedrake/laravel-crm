<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\Quantity;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmActivities;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Order extends Model
{
    use BelongsToTeams;
    use HasCrmActivities;
    use HasCrmFields;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $searchable = [
        'reference',
        'order_id',
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
        return config('laravel-crm.db_table_prefix').'orders';
    }

    public function getOrderIdAttribute($value)
    {
        if ($value) {
            return $value;
        } else {
            // Off the memoised settings map rather than a fresh
            // crm_settings query. This accessor runs for every row of an index
            // page and for every document a mail template renders.
            return app('laravel-crm.settings')->get('order_prefix').$this->number;
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

    public function getTitleAttribute()
    {
        return money($this->total, $this->currency).' - '.($this->organization->name ?? $this->person->name ?? null);
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

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
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

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function getBillingAddress()
    {
        return $this->addresses()->where('address_type_id', 5)->first();
    }

    public function getShippingAddress()
    {
        return $this->addresses()->where('address_type_id', 6)->first();
    }

    public function invoiceComplete()
    {
        foreach ($this->orderProducts as $orderProduct) {
            $quantity = Quantity::toFloat($orderProduct->quantity);

            foreach ($this->invoices as $invoice) {
                // Through the collection rather than the query builder, so a
                // caller that eager loaded invoices.invoiceLines pays nothing
                // here. Uneager-loaded this is still one query per invoice
                // rather than one per invoice per line.
                if ($invoiceLine = $invoice->invoiceLines->firstWhere('order_product_id', $orderProduct->id)) {
                    $quantity -= Quantity::toFloat($invoiceLine->quantity);
                }
            }

            // Not `> 0`: subtracting decimal quantities leaves binary float
            // dust (1.1 less 0.7 less 0.4 is 1.11e-16), which would leave the
            // order showing as not fully invoiced forever.
            if (Quantity::isPositive($quantity)) {
                return false;
            }
        }

        return true;
    }

    public function deliveryComplete()
    {
        foreach ($this->orderProducts as $orderProduct) {
            $quantity = Quantity::toFloat($orderProduct->quantity);

            foreach ($this->deliveries as $delivery) {
                // See invoiceComplete() — read the loaded collection.
                if ($deliveryProduct = $delivery->deliveryProducts->firstWhere('order_product_id', $orderProduct->id)) {
                    $quantity -= Quantity::toFloat($deliveryProduct->quantity);
                }
            }

            // See invoiceComplete() - the same float dust applies.
            if (Quantity::isPositive($quantity)) {
                return false;
            }
        }

        return true;
    }
}
