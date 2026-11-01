<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Support\Money;

class InvoicePay extends Component
{
    use AuthorizesRequests, Toast;

    public bool $showPayInvoice = false;

    public $invoice;

    public $amount;

    public $amount_paid;

    public $amount_due;

    /**
     * Returns validation rules.
     *
     * Any amount up to what is still outstanding - a part payment is fine,
     * an overpayment is not. This used to be `size:` the amount due, which
     * refused anything but the exact balance, and the balance itself was
     * never set on a new invoice.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'amount' => 'required|numeric|gt:0|lte:'.($this->outstandingCents() / 100),
        ];
    }

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;

        $this->amount_due = $this->outstandingCents() / 100;
        $this->amount_paid = ((int) $invoice->amount_paid) / 100;

        if ($this->amount_due > 0) {
            $this->amount = $this->amount_due;
        }
    }

    public function pay()
    {
        $this->authorize('update', $this->invoice);

        // The input is masked ("1,234.56"), so normalise it before the
        // numeric rules see it.
        if ($this->amount !== null && $this->amount !== '') {
            $this->amount = Money::toFloat($this->amount);
        }

        $this->validate();

        $invoice = $this->invoice->fresh();

        // In cents throughout, so a run of part payments adds up exactly.
        $paid = (int) $invoice->getRawOriginal('amount_paid') + Money::toInteger($this->amount);
        $due = (int) $invoice->getRawOriginal('total') - $paid;

        $invoice->update([
            'amount_paid' => $paid / 100,
            'amount_due' => $due / 100,
            'fully_paid_at' => $due <= 0 ? Carbon::now() : null,
        ]);

        $this->invoice = $invoice;
        $this->amount_paid = $paid / 100;
        $this->amount_due = $due / 100;

        $this->success(
            ucfirst(trans('laravel-crm::lang.invoice_paid'))
        );

        $this->resetFields();

        $this->showPayInvoice = false;
    }

    /**
     * What is left to pay, in cents, worked out from the invoice's total and
     * what has been paid - not from the stored amount_due, which older
     * invoices may never have had set.
     */
    protected function outstandingCents(): int
    {
        $invoice = $this->invoice->fresh() ?? $this->invoice;

        return max(0, (int) $invoice->getRawOriginal('total') - (int) $invoice->getRawOriginal('amount_paid'));
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
