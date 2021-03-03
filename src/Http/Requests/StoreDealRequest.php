<?php

namespace VentureDrake\LaravelCrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealRequest extends FormRequest
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
            'person_name' => 'required_without:organisation_name|max:255',
            'organisation_name' => 'required_without:person_name|max:255',
            'title' => 'required|max:255',
            'amount' => 'nullable|numeric',
            'user_assigned_id' => 'required',
        ];
    }
}
