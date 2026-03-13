<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Carbon\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;

class InvoicePay extends Component
{
    use Toast;

    public bool $showPayInvoice = false;

    public $invoice;

    public $amount;

    public $amount_paid;

    public $amount_due;

    /**
     * Returns validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'amount' => 'required|numeric|size:'.($this->invoice->amount_due / 100),
        ];
    }

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;

        $this->amount_due = $invoice->amount_due / 100;
        $this->amount_paid = $invoice->amount_paid / 100;

        if ($this->amount_due > 0) {
            $this->amount = $this->amount_due;
        }
    }

    public function pay()
    {
        $this->validate();

        $this->amount_due = ($this->invoice->amount_due / 100) - ($this->amount + ($this->invoice->amount_paid / 100));

        $this->invoice->update([
            'amount_paid' => $this->amount + ($this->invoice->amount_paid / 100),
            'amount_due' => $this->amount_due,
            'fully_paid_at' => (($this->amount_due == 0) ? Carbon::now() : null),
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.invoice_paid'))
        );

        $this->resetFields();

        $this->showPayInvoice = false;
    }

    private function resetFields()
    {
        $this->reset('amount');
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-pay');
    }
}
