<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use VentureDrake\LaravelCrm\Http\Rules\Api\V2\ScopedExists;

/**
 * Shared by `people/{person}/positions` and
 * `organizations/{organization}/positions`. The URL names one side of the
 * position; the body names the other.
 */
class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');
        $person = $this->route('person');

        return [
            'title' => ['required', 'string', 'max:255'],
            // Defaults to today.
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:65535'],
            // Under a person: defaults to the person's own organisation, so
            // it is only required when they have none.
            'organization_id' => $person
                ? [Rule::requiredIf(! $person->organization_id), 'nullable', 'string', 'uuid', ScopedExists::for($prefix.'organizations', 'external_id')]
                : ['prohibited'],
            // Under an organisation: the person who holds the position.
            'person_id' => $person
                ? ['prohibited']
                : ['required', 'string', 'uuid', ScopedExists::for($prefix.'people', 'external_id')],
            // False keeps the positions this one would otherwise end.
            'end_conflicting' => ['sometimes', 'boolean'],
        ];
    }
}
