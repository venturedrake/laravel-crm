<?php

namespace VentureDrake\LaravelCrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;

class StorePurchaseOrderRequest extends FormRequest
{
    use HasGlobalSettings;

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
        $rules = [
            'issue_date' => 'required|date_format:"'.$this->dateFormat().'"',
            'delivery_date' => 'nullable|date_format:"'.$this->dateFormat().'"',
            'currency' => 'required',
        ];

        if (! request('order_id')) {
            $rules['person_name'] = 'required_without:organisation_name|max:255';
            $rules['organisation_name'] = 'required_without:person_name|max:255';
        }

        if(request('delivery_type') == 'deliver') {
            $rules['delivery_address'] = 'required';
        }

        return $rules;
    }
}
