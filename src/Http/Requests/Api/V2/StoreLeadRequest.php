<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\Concerns\ValidatesContactDetails;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\OwnerInCurrentTeam;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\ScopedExists;

class StoreLeadRequest extends FormRequest
{
    use ValidatesContactDetails;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');

        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'expected_close' => ['nullable', 'string', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
            'person_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'people', 'external_id')],
            'organization_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'organizations', 'external_id')],
            'lead_source_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'lead_sources', 'external_id')],
            'pipeline_stage_id' => ['nullable', 'string', 'uuid', ScopedExists::for($prefix.'pipeline_stages', 'external_id')],
            'user_owner_id' => ['nullable', 'integer', 'exists:users,id', new OwnerInCurrentTeam],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'uuid', ScopedExists::for($prefix.'labels', 'external_id')],
        ], $this->contactDetailRules());
    }
}
