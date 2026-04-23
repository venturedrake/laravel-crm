<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Invoices\Traits\HasInvoiceCommon;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;

class InvoiceCreate extends Component
{
    use HasInvoiceCommon;
    use HasOrganizationSuggest;
    use HasPersonSuggest;

    public $lead_id;

    public $deal_id;

    public ?Quote $quote = null;

    public $quote_id;

    public ?Order $order = null;

    public $order_id;

    protected $listeners = [
        'model-products-updated' => 'updateProducts',
    ];

    public function mount()
    {
        $this->mountCommon();

        $this->currency = app('laravel-crm.settings')->get('currency', 'USD');
        $this->pipeline_stage_id = $this->pipeline->pipelineStages->first()->id ?? null;
        $this->user_owner_id = auth()->user()->id;
        $this->terms = app('laravel-crm.settings')->get('invoice_terms');

        switch ($this->fromModelType) {
            case 'order':
                if ($order = Order::find($this->fromModelId)) {
                    $this->lead_id = $order->lead_id;
                    $this->deal_id = $order->deal_id;
                    $this->quote_id = $order->quote_id;
                    $this->fromModel = $order;
                    $this->order = $order;
                    $this->order_id = $order->id;
                    $this->organization_id = $order->organization ? $order->organization->id : null;
                    $this->organization_name = $order->organization ? $order->organization->name : null;
                    $this->person_id = $order->person ? $order->person->id : null;
                    $this->person_name = $order->person ? $order->person->name : null;
                    $this->currency = $order->currency;
                }
                break;
        }

        if (request()->has('stage')) {
            $this->pipeline_stage_id = request()->stage;
        }
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

        $invoice = $this->invoiceService->create($request, $person ?? null, $organization ?? null);

        $this->saveCustomFields($invoice);

        $this->success(
            ucfirst(trans('laravel-crm::lang.invoice_created')),
            redirectTo: route('laravel-crm.invoices.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-create');
    }
}
