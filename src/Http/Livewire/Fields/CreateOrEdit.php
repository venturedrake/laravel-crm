<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Fields;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class CreateOrEdit extends Component
{
    use NotifyToast;

    public Field $field;

    public $fieldType;

    public $fieldOptions = [];

    public $fieldGroup;

    public $fieldName;

    public $fieldDefault;

    public $fieldRequired = false;

    public $fieldModels = [];

    protected $rules = [
        'fieldType' => 'required',
        'fieldName' => 'required|max:255',
    ];

    public function mount()
    {
        if(isset($this->field)) {
            $this->fieldName = $this->field->name;
            $this->fieldType = $this->field->type;
            $this->fieldGroup = $this->field->fieldGroup->id ?? null;
            $this->fieldDefault = $this->field->default;
            $this->fieldRequired = $this->field->required;
            $this->fieldModels = FieldModel::where('field_id', $this->field->id)->get()->pluck('model')->toArray();

            foreach($this->field->fieldOptions as $fieldOption) {
                $this->fieldOptions[] = [
                    'id' => $fieldOption->id,
                    'value' => $fieldOption->value,
                    'label' => $fieldOption->label,
                    'order' => $fieldOption->order,
                ];
            }

        } else {
            $this->fieldType = 'text';
        }
    }

    public function updatedFieldType($value)
    {
        switch($value) {
            case "select":
            case "checkbox_multiple":
            case "radio":
                if(count($this->fieldOptions) == 0) {
                    $this->addOption();

                }
                break;

        }
    }

    public function addOption()
    {
        $this->fieldOptions[] = [
            'id' => null,
            'value' => '',
            'label' => '',
            'order' => count($this->fieldOptions) + 1,
        ];
    }

    public function removeOption($key)
    {
        unset($this->fieldOptions[$key]);
    }

    public function submit()
    {
        $this->validate();

        if(isset($this->field)) {
            $this->field->update([
                'type' => $this->fieldType,
                'name' => $this->fieldName,
                'field_group_id' => $this->fieldGroup,
                'required' => $this->fieldRequired,
                'default' => $this->fieldDefault
            ]);

            if($this->fieldOptions) {
                $fieldOptionIds = [];
                foreach($this->fieldOptions as $option) {
                    if($fieldOption = FieldOption::find($option['id'])) {
                        $fieldOptionIds[] = $option['id'];
                        $fieldOption->update([
                            'value' => $option['value'],
                            'label' => $option['label'],
                            'order' => $option['order'],
                        ]);
                    }
                }

                foreach ($this->field->fieldOptions as $fieldOption) {
                    if (! in_array($fieldOption->id, $fieldOptionIds)) {
                        $fieldOption->delete();
                    }
                }
            }

            flash(ucfirst(trans('laravel-crm::lang.field_updated')))->success()->important();
        } else {
            $this->field = Field::create([
                'type' => $this->fieldType,
                'name' => $this->fieldName,
                'field_group_id' => $this->fieldGroup,
                'required' => $this->fieldRequired,
                'default' => $this->fieldDefault
            ]);

            if($this->fieldOptions) {
                foreach($this->fieldOptions as $option) {
                    $this->field->fieldOptions()->create([
                        'value' => $option['value'],
                        'label' => $option['label'],
                        'order' => $option['order'],
                    ]);
                }
            }

            flash(ucfirst(trans('laravel-crm::lang.field_stored')))->success()->important();
        }

        $this->syncFieldModels();

        return redirect()->to(route('laravel-crm.fields.index'));
    }

    protected function syncFieldModels()
    {
        if ($this->fieldModels) {
            foreach ($this->fieldModels as $model) {
                FieldModel::firstOrCreate([
                    'field_id' => $this->field->id,
                    'model' => $model,
                ]);
            }
        }

        foreach (FieldModel::where('field_id', $this->field->id)->get() as $fieldModel) {
            if (! in_array($fieldModel->model, $this->fieldModels)) {
                $fieldModel->delete();
            }
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.fields.create-or-edit');
    }
}
