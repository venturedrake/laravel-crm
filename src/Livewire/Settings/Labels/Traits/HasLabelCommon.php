<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Labels\Traits;

use Mary\Traits\Toast;

trait HasLabelCommon
{
    use Toast;

    public $name;

    public $hex;

    public $description;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'hex' => 'required|max:7',
        ];
    }

    protected function messages()
    {
        return [
            'hex.required' => 'The '.trans('laravel-crm::lang.color').' is required.',
        ];
    }
}
