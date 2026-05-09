<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\LeadSources;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class LeadSourceIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public $dateFormat;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'leads_count', 'label' => ucfirst(__('laravel-crm::lang.leads')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field?->format($this->dateFormat)],
            ['key' => 'updated_at', 'label' => ucfirst(__('laravel-crm::lang.updated')), 'format' => fn ($row, $field) => $field?->format($this->dateFormat)],
        ];
    }

    public function leadSources(): LengthAwarePaginator
    {
        return LeadSource::withCount('leads')
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($leadSource = LeadSource::find($id)) {
            $leadSource->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.lead_source_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.lead-sources.lead-source-index', [
            'headers' => $this->headers(),
            'leadSources' => $this->leadSources(),
        ]);
    }
}
