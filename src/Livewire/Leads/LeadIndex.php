<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Models\Lead;

class LeadIndex extends Component
{
    use WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    protected $headers;

    protected $leads;

    public function mount()
    {
        $this->headers = [
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'title', 'label' => ucfirst(__('laravel-crm::lang.title'))],
            ['key' => 'labels', 'label' => ucfirst(__('laravel-crm::lang.labels')), 'format' => fn ($row, $field) => $field],
            ['key' => 'amount', 'label' => ucfirst(__('laravel-crm::lang.value')), 'format' => fn ($row, $field) => money($field, $row->currency)],
            ['key' => 'person.name', 'label' => ucfirst(__('laravel-crm::lang.contact'))],
            ['key' => 'organization.name', 'label' => ucfirst(__('laravel-crm::lang.organization'))],
            ['key' => 'pipeline_stage', 'label' => ucfirst(__('laravel-crm::lang.stage'))],
            ['key' => 'ownerUser.name', 'label' => 'Owner', 'format' => fn ($row, $field) => $field ?? ucfirst(__('laravel-crm::lang.unallocated'))],
        ];

        $this->leads = Lead::orderBy(...array_values($this->sortBy))->paginate(10);
    }

    public function render()
    {
        return view('laravel-crm::livewire.leads.lead-index', [
            'headers' => $this->headers,
            'leads' => $this->leads,
        ]);
    }
}
