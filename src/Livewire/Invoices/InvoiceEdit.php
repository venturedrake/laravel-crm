<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Invoices\Traits\HasInvoiceCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

class InvoiceEdit extends Component
{
    use AuthorizesRequests, HasInvoiceCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public Invoice $invoice;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount(Invoice $invoice)
    {
        $this->mountCommon($invoice);

        $this->invoice = $invoice;
        $this->organization_id = $invoice->organization ? $invoice->organization->id : null;
        $this->organization_name = $invoice->organization ? $invoice->organization->name : null;
        $this->person_id = $invoice->person ? $invoice->person->id : null;
        $this->person_name = $invoice->person ? $invoice->person->name : null;
        $this->reference = $invoice->reference;
        $this->currency = $invoice->currency;
        $this->issue_date = $invoice->issue_date?->format('Y-m-d');
        $this->due_date = $invoice->due_date?->format('Y-m-d');
        $this->terms = $invoice->terms;
        $this->pipeline_stage_id = $invoice->pipelineStage->id ?? null;
        $this->user_owner_id = $invoice->ownerUser->id ?? null;

        $this->loadCustomFields($invoice);
    }

    public function save()
    {
        $this->authorize('update', $this->invoice);

        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        // One transaction, so a failure part-way - in the service or in
        // the custom fields - leaves no contact, document or lines behind.
        // The service refuses a total change once a payment is recorded; the
        // form has no field for that error, so it is shown as a toast.
        try {
            DB::transaction(function () use ($request) {
                // First, before any plain read fixes this transaction's
                // snapshot - see InvoiceService::lockForEdit().
                $this->invoiceService->lockForEdit($this->invoice);

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

                $this->personService->linkToOrganization($person ?? null, $organization ?? null);

                $this->invoiceService->update($request, $this->invoice, $person ?? null, $organization ?? null);

                $this->saveCustomFields($this->invoice->fresh());
            });
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->first());

            return;
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.invoice_updated')),
            redirectTo: route('laravel-crm.invoices.show', $this->invoice)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-edit');
    }
}
