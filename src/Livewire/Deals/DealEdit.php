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

class DealEdit extends Component
{
    use HasDealCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public ?Deal $deal = null;

    public function mount(Deal $deal)
    {
        $this->mountCommon();

        $this->deal = $deal;
        $this->organization_id = $deal->organization ? $deal->organization->id : null;
        $this->organization_name = $deal->organization ? $deal->organization->name : null;
        $this->person_id = $deal->person ? $deal->person->id : null;
        $this->person_name = $deal->person ? $deal->person->name : null;
        $this->title = $deal->title;
        $this->description = $deal->description;
        $this->amount = $deal->amount;
        $this->currency = $deal->currency;
        $this->pipeline = Pipeline::where('model', get_class(new Deal))->first();
        $this->pipeline_stage_id = $deal->pipelineStage->id ?? null;
        $this->expected_close = ($deal->expected_close) ? $deal->expected_close->format('Y-m-d') : null;
        $this->labels = $deal->labels->pluck('id')->toArray();
        $this->user_owner_id = $deal->ownerUser->id ?? null;

        foreach ($deal->dealProducts()->whereNotNull('product_id')->get() as $dealProduct) {
            $this->products[] = [
                'deal_product_id' => $dealProduct->id,
                'id' => $dealProduct->product->id,
                'price' => $dealProduct->price / 100,
                'quantity' => $dealProduct->quantity,
                'amount' => $dealProduct->amount / 100,
            ];
        }

        /*if ($address = $deal->getPrimaryAddress()) {
            $this->address_line_1 = $address->line_1;
            $this->address_line_2 = $address->line_2;
            $this->address_line_3 = $address->line_3;
            $this->address_suburb = $address->suburb;
            $this->address_state = $address->state;
            $this->address_postcode = $address->postcode;
            $this->address_country = $address->country;
        }

        if ($email = $deal->getPrimaryEmail()) {
            $this->email = $email->address;
            $this->email_type = $email->type;
        }

        if ($phone = $deal->getPrimaryPhone()) {
            $this->phone = $phone->number;
            $this->phone_type = $phone->type;
        }*/

        $this->loadCustomFields($deal);
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

        $this->dealService->update($request, $this->deal, $person ?? null, $organization ?? null);

        $this->saveCustomFields($this->deal->fresh());

        $this->success(
            ucfirst(trans('laravel-crm::lang.deal_updated')),
            redirectTo: route('laravel-crm.deals.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.deals.deal-edit');
    }
}
