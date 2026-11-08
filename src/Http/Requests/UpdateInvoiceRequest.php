<?php

namespace VentureDrake\LaravelCrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use VentureDrake\LaravelCrm\Http\Rules\ContactEmail;
use VentureDrake\LaravelCrm\Http\Rules\PhoneNumber;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;

class UpdateInvoiceRequest extends FormRequest
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
            'person_name' => 'required_without:organization_name|max:255',
            'organization_name' => 'required_without:person_name|max:255',
            'phone' => ['nullable', new PhoneNumber],
            'email' => ['nullable', new ContactEmail],
            'issue_date' => 'required|date_format:"'.$this->dateFormat().'"',
            'due_date' => 'required|date_format:"'.$this->dateFormat().'"|after_or_equal:issue_date',
            'currency' => 'required',
        ];

        /*if (! Xero::isConnected()) {
            $rules['number'] = 'required|integer|unique:VentureDrake\LaravelCrm\Models\Invoice,number,'.$this->invoice->id;
        }*/

        return $rules;
    }
}
