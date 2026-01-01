<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Invoices\Traits\HasInvoiceCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;

class InvoiceCreate extends Component
{
    use HasInvoiceCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public function mount()
    {
        $this->currency = \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD';
        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;
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

        /* $this->leadService->create($request, $person ?? null, $organization ?? null); */

        $this->success(
            ucfirst(trans('laravel-crm::lang.invoice_created_successfully')),
            redirectTo: route('laravel-crm.invoices.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-create');
    }
}
