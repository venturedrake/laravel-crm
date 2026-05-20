<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'vat_number' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'number_of_employees' => ['nullable', 'integer', 'min:0'],
            'annual_revenue' => ['nullable', 'numeric', 'min:0'],
            'total_money_raised' => ['nullable', 'numeric', 'min:0'],
            'organization_type_id' => ['nullable', 'integer'],
            'industry_id' => ['nullable', 'integer'],
            'timezone_id' => ['nullable', 'integer'],
            'user_owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
