<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Quotes\Traits\HasQuoteCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteCreate extends Component
{
    use HasOrganizationSuggest;
    use HasPersonSuggest;
    use HasQuoteCommon;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount()
    {
        $this->mountCommon();
        
        $this->currency = app('laravel-crm.settings')->get('currency', 'USD');
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;
        $this->terms = app('laravel-crm.settings')->get('quote_terms');
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
        $this->title = trim($value).' '.ucfirst(trans('laravel-crm::lang.quote'));
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

        $this->quoteService->create($request, $person ?? null, $organization ?? null);

        $this->success(
            ucfirst(trans('laravel-crm::lang.quote_created_successfully')),
            redirectTo: route('laravel-crm.quotes.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.quotes.quote-create');
    }
}
