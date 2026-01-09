<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\Traits;

use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\FieldModel;

trait HasCustomFieldCommon
{
    use Toast;

    public $name;

    public array $types = [
        ['id' => 'text', 'name' => 'Single-line text'],
        ['id' => 'textarea', 'name' => 'Multi-line text'],
        ['id' => 'checkbox', 'name' => 'Single checkbox'],
        ['id' => 'checkbox_multiple', 'name' => 'Multiple checkbox'],
        ['id' => 'select', 'name' => 'Dropdown select'],
        ['id' => 'radio', 'name' => 'Radio select'],
        ['id' => 'date', 'name' => 'Date picker'],
    ];

    public $type;

    public array $options = [];

    public array $groups = [];

    public $field_group_id = null;

    public $default;

    public $required = false;

    public array $models = [];

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }

    public function mountCommon()
    {
        $this->groups = ['' => ''] + \VentureDrake\LaravelCrm\Models\FieldGroup::orderBy('name')->get()->toArray();
    }

    public function updatedType($value): void
    {
        switch ($value) {
            case 'select':
            case 'checkbox_multiple':
            case 'radio':
                if (count($this->options) == 0) {
                    $this->addOption();

                }
                break;

        }
    }

    public function addOption(): void
    {
        $this->options[] = [
            'id' => null,
            'value' => '',
            'label' => '',
            'order' => count($this->options) + 1,
        ];
    }

    public function removeOption($key): void
    {
        unset($this->options[$key]);
    }

    protected function syncFieldModels($field): void
    {
        if ($this->models) {
            foreach ($this->models as $model) {
                FieldModel::firstOrCreate([
                    'field_id' => $field->id,
                    'model' => $model,
                ]);
            }
        } else {
            $this->models = [];
        }

        foreach (FieldModel::where('field_id', $field->id)->get() as $fieldModel) {
            if (! in_array($fieldModel->model, $this->models)) {
                $fieldModel->delete();
            }
        }
    }
}
