<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\OwnerInCurrentTeam;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_rate' => ['nullable', 'numeric'],
            'tax_rate_id' => ['nullable', 'integer', "exists:{$prefix}tax_rates,id"],
            'product_category_id' => ['nullable', 'string', 'uuid', "exists:{$prefix}product_categories,external_id"],
            'purchase_account' => ['nullable', 'string', 'max:255'],
            'sales_account' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'user_owner_id' => ['nullable', 'integer', 'exists:users,id', new OwnerInCurrentTeam],
        ];
    }
}
