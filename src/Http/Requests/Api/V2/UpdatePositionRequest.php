<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            // An explicit null makes the position current again.
            'end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:65535'],
            // A position stays with its person and organisation.
            'person_id' => ['prohibited'],
            'organization_id' => ['prohibited'],
        ];
    }
}
