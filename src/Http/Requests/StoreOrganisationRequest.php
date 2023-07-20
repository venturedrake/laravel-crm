<?php

namespace VentureDrake\LaravelCrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganisationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'user_owner_id' => 'required',
            'phones.*.type' => 'required_with:phones.*.number',
            'emails.*.type' => 'required_with:emails.*.address'
        ];
    }

    public function messages()
    {
        return [
            'phones.*.type.required_with' => 'The type field is required',
            'emails.*.type.required_with' => 'The type field is required'
        ];
    }
}
