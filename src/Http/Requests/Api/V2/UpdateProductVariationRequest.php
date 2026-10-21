<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            // Absent leaves the price alone; an explicit null removes the
            // variation's price in `currency`, so the product price applies.
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            // Defaults to the product's price currency.
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
