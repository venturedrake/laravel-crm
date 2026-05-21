<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Features;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class FeatureStatusIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'order', 'direction' => 'asc'];

    public function headers()
    {
        return [
            ['key' => 'order', 'label' => ucfirst(__('laravel-crm::lang.order'))],
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'color', 'label' => ucfirst(__('laravel-crm::lang.color')), 'sortable' => false],
            ['key' => 'is_default', 'label' => 'Default', 'format' => fn ($row, $field) => $field ? 'Yes' : 'No'],
            ['key' => 'is_closed', 'label' => 'Closed', 'format' => fn ($row, $field) => $field ? 'Yes' : 'No'],
            ['key' => 'features_count', 'label' => 'Features', 'sortable' => false],
        ];
    }

    public function featureStatuses(): LengthAwarePaginator
    {
        return FeatureStatus::withCount('features')
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($featureStatus = FeatureStatus::find($id)) {
            $featureStatus->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.deleted')));
        }
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            FeatureStatus::whereKey($id)->update(['order' => $index + 1]);
        }

        $this->success(ucfirst(trans('laravel-crm::lang.updated')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.feature-statuses.feature-status-index', [
            'headers' => $this->headers(),
            'featureStatuses' => $this->featureStatuses(),
        ]);
    }
}
