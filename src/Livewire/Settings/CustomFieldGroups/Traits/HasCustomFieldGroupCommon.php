<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups\Traits;

use Mary\Traits\Toast;

trait HasCustomFieldGroupCommon
{
    use Toast;

    public $name;

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }
}
