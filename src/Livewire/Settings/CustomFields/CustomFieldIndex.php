<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFields;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class CustomFieldIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function headers()
    {
        return [
            ['key' => 'type', 'label' => ucfirst(__('laravel-crm::lang.type'))],
            ['key' => 'fieldGroup.name', 'label' => ucfirst(__('laravel-crm::lang.group')), 'sortable' => false],
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'required', 'label' => ucfirst(__('laravel-crm::lang.required')), 'format' => fn ($row, $field) => $field ? ucfirst(__('laravel-crm::lang.yes')) : ucfirst(__('laravel-crm::lang.no'))],
            ['key' => 'default', 'label' => ucfirst(__('laravel-crm::lang.default'))],
            ['key' => 'system', 'label' => ucfirst(__('laravel-crm::lang.system')), 'format' => fn ($row, $field) => $field ? ucfirst(__('laravel-crm::lang.yes')) : ucfirst(__('laravel-crm::lang.no'))],
            ['key' => 'attached_to', 'label' => ucfirst(__('laravel-crm::lang.attached_to')), 'sortable' => false],
        ];
    }

    public function fields(): LengthAwarePaginator
    {
        return Field::orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($field = Field::find($id)) {
            $field->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.custom_field_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.custom-fields.custom-field-index', [
            'headers' => $this->headers(),
            'fields' => $this->fields(),
        ]);
    }
}
