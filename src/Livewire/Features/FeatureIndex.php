<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class FeatureIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $feature_status_id = [];

    #[Url]
    public ?bool $is_public = null;

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public function filterCount(): int
    {
        return (count($this->feature_status_id) > 0 ? 1 : 0)
            + ($this->is_public !== null ? 1 : 0);
    }

    public function statuses(): Collection
    {
        return FeatureStatus::orderBy('order')->orderBy('id')->get();
    }

    public function headers()
    {
        return [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field?->diffForHumans()],
            ['key' => 'feature_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'title', 'label' => ucfirst(__('laravel-crm::lang.title'))],
            ['key' => 'status.name', 'label' => ucfirst(__('laravel-crm::lang.status')), 'sortable' => false],
            ['key' => 'votes_count', 'label' => 'Votes'],
            ['key' => 'comments_count', 'label' => 'Comments'],
            ['key' => 'is_public', 'label' => 'Public', 'format' => fn ($row, $field) => $field ? 'Yes' : 'No'],
        ];
    }

    public function features(): LengthAwarePaginator
    {
        return Feature::query()
            ->when($this->search, fn (Builder $q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->feature_status_id, fn (Builder $q) => $q->whereIn('feature_status_id', $this->feature_status_id))
            ->when($this->is_public !== null, fn (Builder $q) => $q->where('is_public', $this->is_public))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($feature = Feature::find($id)) {
            $feature->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.feature_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.features.feature-index', [
            'headers' => $this->headers(),
            'features' => $this->features(),
            'statuses' => $this->statuses(),
            'filterCount' => $this->filterCount(),
        ]);
    }
}
