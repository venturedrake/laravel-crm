<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\SortsTable;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class CustomFieldGroupIndex extends Component
{
    use AuthorizesRequests;
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;
    use SortsTable;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'system', 'label' => ucfirst(__('laravel-crm::lang.system')), 'format' => fn ($row, $field) => $field ? ucfirst(__('laravel-crm::lang.yes')) : ucfirst(__('laravel-crm::lang.no'))],
            ['key' => 'fields', 'label' => ucfirst(__('laravel-crm::lang.fields')), 'format' => fn ($row, $field) => count($field), 'sortable' => false],
        ];
    }

    public function fieldGroups(): LengthAwarePaginator
    {
        return FieldGroup::orderBy(...$this->tableSort())
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($fieldGroup = FieldGroup::find($id)) {
            $this->authorize('delete', $fieldGroup);

            $fieldGroup->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.custom_field_group_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.custom-field-groups.custom-field-group-index', [
            'headers' => $this->headers(),
            'fieldGroups' => $this->fieldGroups(),
        ]);
    }
}
