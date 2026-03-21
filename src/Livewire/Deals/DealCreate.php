<?php

namespace VentureDrake\LaravelCrm\Livewire\Deals;

use Carbon\Carbon;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Deals\Traits\HasDealCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Setting;

class DealCreate extends Component
{
    use HasDealCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public $fromModelType;

    public $fromModelId;

    public $fromModel;

    public $lead_id;

    public function mount()
    {
        $this->mountCommon();

        $this->currency = Setting::currency()->value ?? 'USD';
        $this->pipeline = Pipeline::where('model', get_class(new Deal))->first();
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;

        switch ($this->fromModelType) {
            case 'lead':
                if ($lead = Lead::find($this->fromModelId)) {
                    $this->fromModel = $lead;
                    $this->lead_id = $lead->id;
                    $this->organization_id = $lead->organization ? $lead->organization->id : null;
                    $this->organization_name = $lead->organization ? $lead->organization->name : null;
                    $this->person_id = $lead->person ? $lead->person->id : null;
                    $this->person_name = $lead->person ? $lead->person->name : null;
                    $this->title = $lead->title;
                    $this->description = $lead->description;
                    $this->amount = $lead->amount / 100;
                    $this->currency = $lead->currency;
                }
                break;

            case 'person':
                if ($person = Person::find($this->fromModelId)) {
                    $this->fromModel = $person;
                    $this->person_id = $person->id;
                    $this->person_name = $person->name;
                }
                break;

            case 'organization':
                if ($organization = Organization::find($this->fromModelId)) {
                    $this->fromModel = $organization;
                    $this->organization_id = $organization->id;
                    $this->organization_name = $organization->name;
                }
                break;
        }

        if (request()->has('stage')) {
            $this->pipeline_stage_id = request()->stage;
        }

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
        $this->title = trim($value).' '.ucfirst(trans('laravel-crm::lang.deal'));
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

        switch ($this->fromModelType) {
            case 'lead':
                $this->fromModel->update([
                    'converted_at' => Carbon::now(),
                ]);

                $this->success(
                    ucfirst(trans('laravel-crm::lang.lead_converted_to_deal')),
                    redirectTo: route('laravel-crm.deals.index')
                );
                break;
        }

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
