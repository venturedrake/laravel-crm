<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Deals\Traits\HasDealCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;

class DealCreate extends Component
{
    use HasDealCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public function mount()
    {
        $this->mountCommon();
        
        $this->currency = \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD';
        $this->pipeline = Pipeline::where('model', get_class(new Deal))->first();
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;

        $this->addProduct();
    }

    public function updatedPersonName($value)
    {
        if (! $this->organization_name) {
            $this->generateTitleString($value);
        }
    }

    public function updatedOrganizationName($value)
    {
        $this->generateTitleString($value);
    }

    protected function generateTitleString($value)
    {
        $this->title = trim($value).' '.ucfirst(trans('laravel-crm::lang.lead'));
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        if ($this->person_name && ! $this->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($this->person_id) {
            $person = Person::find($this->person_id);
        }

        if ($this->organization_name && ! $this->organization_id) {
            $organization = $this->organizationService->createFromRelated($request);
        } elseif ($this->organization_id) {
            $organization = Organization::find($this->organization_id);
        }

        $this->dealService->create($request, $person ?? null, $organization ?? null);

        $this->success(
            ucfirst(trans('laravel-crm::lang.deal_created_successfully')),
            redirectTo: route('laravel-crm.deals.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.deals.deal-create');
    }
}
