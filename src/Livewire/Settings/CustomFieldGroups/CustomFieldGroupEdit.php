<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups\Traits\HasCustomFieldGroupCommon;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class CustomFieldGroupEdit extends Component
{
    use AuthorizesRequests;
    use HasCustomFieldGroupCommon;

    public FieldGroup $fieldGroup;

    public function mount()
    {
        $this->name = $this->fieldGroup->name;
    }

    public function save()
    {
        $this->authorize('update', $this->fieldGroup);

        $this->validate();

        $this->fieldGroup->update([
            'name' => $this->name,
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.custom_field_group_updated')),
            redirectTo: route('laravel-crm.field-groups.show', $this->fieldGroup)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.custom-field-groups.custom-field-group-edit');
    }
}
