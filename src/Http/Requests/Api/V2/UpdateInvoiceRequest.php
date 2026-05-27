<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\OwnerInCurrentTeam;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');
        $iso8601 = 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z';

        return [
            'reference' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'string', $iso8601],
            'due_date' => ['nullable', 'string', $iso8601],
            'currency' => ['nullable', 'string', 'size:3'],
            'terms' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'total' => ['nullable', 'numeric'],
            'person_id' => ['nullable', 'string', 'uuid', "exists:{$prefix}people,external_id"],
            'organization_id' => ['nullable', 'string', 'uuid', "exists:{$prefix}organizations,external_id"],
            'user_owner_id' => ['nullable', 'integer', 'exists:users,id', new OwnerInCurrentTeam],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'uuid', "exists:{$prefix}labels,external_id"],
            'line_items' => ['nullable', 'array'],
            'line_items.*.id' => ['nullable', 'string', 'uuid'],
            'line_items.*.product_id' => ['required', 'string', 'uuid', "exists:{$prefix}products,external_id"],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.amount' => ['required', 'numeric', 'min:0'],
            'line_items.*.comments' => ['nullable', 'string'],
        ];
    }
}
