<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Pipeline;

class InvoiceShow extends Component
{
    use Toast;

    public Invoice $invoice;

    public $email;

    public $phone;

    public $address;

    public $taxName;

    public $timezone;

    public ?Pipeline $pipeline = null;

    protected $listeners = [
        'refreshInvoice' => '$refresh',
    ];

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;

        if ($invoice->person) {
            $this->email = $invoice->person->getPrimaryEmail();
            $this->phone = $invoice->person->getPrimaryPhone();
        }

        if ($invoice->organization) {
            $this->address = $invoice->organization->getPrimaryAddress();
        }

        $this->pipeline = Pipeline::where('model', get_class(new Invoice))->first();
        $this->taxName = app('laravel-crm.settings')->get('tax_name', 'Tax');
        $this->timezone = app('laravel-crm.settings')->get('timezone', 'UTC');
    }

    public function delete($id)
    {
        if ($invoice = Invoice::find($id)) {
            $invoice->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.invoice_deleted')), redirectTo: route('laravel-crm.invoices.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-show');
    }
}
