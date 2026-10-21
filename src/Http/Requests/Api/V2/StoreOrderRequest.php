<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\Concerns\ValidatesLineItemVariations;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\OwnerInCurrentTeam;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\ScopedExists;

class StoreOrderRequest extends FormRequest
{
    use ValidatesLineItemVariations;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');

        return [
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'size:3'],
            'terms' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'adjustments' => ['nullable', 'numeric'],
            // Removed at 2.4.0: both are computed from `line_items`. Rejected
            // rather than silently dropped, so a client still sending its own
            // authoritative totals gets a 422 naming the cause instead of
            // different numbers back with no error.
            'subtotal' => ['prohibited'],
            'total' => ['prohibited'],
            'person_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'people', 'external_id')],
            'organization_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'organizations', 'external_id')],
            'lead_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'leads', 'external_id')],
            'deal_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'deals', 'external_id')],
            'quote_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'quotes', 'external_id')],
            'user_owner_id' => ['nullable', 'integer', 'exists:users,id', new OwnerInCurrentTeam],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'uuid', ScopedExists::for($prefix.'labels', 'external_id')],
            'line_items' => ['nullable', 'array'],
            'line_items.*.product_id' => ['required', 'string', 'uuid', ScopedExists::for($prefix.'products', 'external_id')],
            'line_items.*.product_variation_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'product_variations', 'external_id')],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.amount' => ['required', 'numeric', 'min:0'],
            'line_items.*.comments' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'subtotal.prohibited' => 'The subtotal is calculated from line_items and can no longer be set on the request. Remove it from the payload.',
            'total.prohibited' => 'The total is calculated from line_items and can no longer be set on the request. Remove it from the payload.',
        ];
    }
}
