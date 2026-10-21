<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            // Major units, like every other API amount. Null or absent leaves
            // the variation without a price of its own, so a line on it uses
            // the product price.
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            // Defaults to the product's price currency.
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
