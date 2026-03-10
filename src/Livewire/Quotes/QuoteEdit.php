<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Quotes\Traits\HasQuoteCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteEdit extends Component
{
    use HasOrganizationSuggest;
    use HasPersonSuggest;
    use HasQuoteCommon;

    public ?Quote $quote = null;

    public function mount(Quote $quote)
    {
        $this->mountCommon();

        $this->quote = $quote;
        $this->organization_id = $quote->organization ? $quote->organization->id : null;
        $this->organization_name = $quote->organization ? $quote->organization->name : null;
        $this->person_id = $quote->person ? $quote->person->id : null;
        $this->person_name = $quote->person ? $quote->person->name : null;
        $this->title = $quote->title;
        $this->description = $quote->description;
        $this->reference = $quote->reference;
        $this->currency = $quote->currency;
        $this->issue_at = $quote->issue_at->format('Y-m-d') ?? null;
        $this->expire_at = $quote->expire_at->format('Y-m-d') ?? null;
        $this->terms = $quote->terms;
        $this->pipeline_stage_id = $quote->pipelineStage->id ?? null;
        $this->labels = $quote->labels->pluck('id')->toArray();
        $this->user_owner_id = $quote->ownerUser->id ?? null;

        $this->sub_total = $quote->subtotal / 100;
        $this->tax = $quote->tax / 100;
        $this->total = $quote->total / 100;
    }

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

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

        $this->quoteService->update($request, $this->quote, $person ?? null, $organization ?? null);

        $this->success(
            ucfirst(trans('laravel-crm::lang.quote_updated_successfully')),
            redirectTo: route('laravel-crm.quotes.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.quotes.quote-edit');
    }
}
