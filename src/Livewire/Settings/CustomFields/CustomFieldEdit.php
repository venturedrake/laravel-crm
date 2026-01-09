<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFields;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\Traits\HasCustomFieldCommon;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;

class CustomFieldEdit extends Component
{
    use HasCustomFieldCommon;

    public Field $field;

    public function save()
    {
        $this->validate();

        $this->field->update([
            'type' => $this->type,
            'name' => $this->name,
            'field_group_id' => $this->field_group_id,
            'required' => $this->required,
            'default' => $this->default,
        ]);

        if ($this->options) {
            $fieldOptionIds = [];
            foreach ($this->options as $index => $option) {
                if ($fieldOption = FieldOption::find($option['id'])) {
                    $fieldOptionIds[] = $option['id'];
                    $fieldOption->update([
                        'value' => $option['value'],
                        'label' => $option['label'],
                        'order' => $option['order'],
                    ]);
                } else {
                    $newOption = $this->field->fieldOptions()->create([
                        'value' => $option['value'],
                        'label' => $option['label'],
                        'order' => $option['order'],
                    ]);
                    $this->options[$index]['id'] = $newOption->id;
                    $fieldOptionIds[] = $newOption->id;
                }
            }

            foreach ($this->field->fieldOptions as $fieldOption) {
                if (! in_array($fieldOption->id, $fieldOptionIds)) {
                    $fieldOption->delete();
                }
            }
        }

        $this->syncFieldModels($this->field);

        $this->success(
            ucfirst(trans('laravel-crm::lang.custom_field_updated')),
            redirectTo: route('laravel-crm.fields.index')
        );
    }

    public function mount()
    {
        $this->mountCommon();

        $this->name = $this->field->name;
        $this->type = $this->field->type;
        $this->field_group_id = $this->field->field_group_id;
        $this->required = $this->field->required;
        $this->default = $this->field->default;

        foreach ($this->field->fieldOptions as $fieldOption) {
            $this->options[] = [
                'id' => $fieldOption->id,
                'value' => $fieldOption->value,
                'label' => $fieldOption->label,
                'order' => $fieldOption->order,
            ];
        }

        foreach (FieldModel::where('field_id', $this->field->id)->get() as $fieldModel) {
            $this->models[] = $fieldModel->model;
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.custom-fields.custom-field-create');
    }
}
