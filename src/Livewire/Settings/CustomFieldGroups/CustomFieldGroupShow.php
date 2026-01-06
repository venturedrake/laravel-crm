<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class CustomFieldGroupShow extends Component
{
    use Toast;

    public FieldGroup $fieldGroup;

    public function render()
    {
        return view('laravel-crm::livewire.settings.custom-field-groups.custom-field-group-show');
    }
}
