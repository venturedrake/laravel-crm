<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFields;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\Traits\HasCustomFieldCommon;
use VentureDrake\LaravelCrm\Models\Field;

class CustomFieldCreate extends Component
{
    use HasCustomFieldCommon;

    public function save()
    {
        $this->validate();

        $field = Field::create([
            'external_id' => Uuid::uuid4()->toString(),
            'type' => $this->type,
            'name' => $this->name,
            'field_group_id' => $this->field_group_id,
            'required' => $this->required,
            'default' => $this->default,
        ]);

        if ($this->options) {
            foreach ($this->options as $option) {
                $field->fieldOptions()->create([
                    'value' => $option['value'],
                    'label' => $option['label'],
                    'order' => $option['order'],
                ]);
            }
        }

        $this->syncFieldModels($field);

        $this->success(
            ucfirst(trans('laravel-crm::lang.custom_field_created')),
            redirectTo: route('laravel-crm.fields.index')
        );
    }

    public function mount()
    {
        $this->mountCommon();
        $this->type = 'text';
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.custom-fields.custom-field-create');
    }
}
