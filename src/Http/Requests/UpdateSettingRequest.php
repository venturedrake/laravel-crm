<?php

namespace VentureDrake\LaravelCrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
            'organisation_name' => 'required|max:255',
            'country' => 'required',
            'language' => 'required',
            'currency' => 'required',
            'timezone' => 'required',
            'date_format' => 'required',
            'time_format' => 'required'
        ];
    }
}
