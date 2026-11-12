<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Services\InvoiceService;
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
     * The limit is read from the database, not from this component, so a
     * drawer left open while another payment landed cannot overpay.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'amount' => 'required|numeric|gt:0|lte:'.($this->outstandingCents() / 100),
        ];
    }

    /**
     * Without this, an overpayment showed Laravel's own text with the bare
     * number: "The amount field must be less than or equal to 6349.54."
     */
    protected function messages()
    {
        return [
            'amount.lte' => ucfirst(__('laravel-crm::lang.payment_more_than_amount_due', [
                'due' => money($this->outstandingCents(), $this->invoice->currency),
            ])),
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

    /**
     * Opens the drawer with the balance as it is now, however long the page
     * has been open and whatever was paid since.
     */
    public function openPay(): void
    {
        $this->syncBalance();

        $this->resetValidation();

        $this->showPayInvoice = true;
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

        // The row is held while the payment is added, so an edit or a second
        // payment running at the same time reads the balance this one
        // leaves, not the one it started from. The amount is checked again
        // against that balance: validate() saw the balance from before the
        // lock, so two tabs paying the last 100.00 both passed it.
        try {
            [$invoice, $amount, $paid, $due] = DB::transaction(function () {
                $invoice = app(InvoiceService::class)->lockForEdit($this->invoice);
                $amount = Money::toInteger($this->amount);
                $outstanding = (int) $invoice->getRawOriginal('total') - (int) $invoice->getRawOriginal('amount_paid');

                if ($amount > $outstanding) {
                    throw ValidationException::withMessages([
                        'amount' => ucfirst(__('laravel-crm::lang.payment_exceeds_amount_due', [
                            'due' => money(max(0, $outstanding), $invoice->currency),
                        ])),
                    ]);
                }

                // In cents throughout, so a run of part payments adds up exactly.
                $paid = (int) $invoice->getRawOriginal('amount_paid') + $amount;
                $due = (int) $invoice->getRawOriginal('total') - $paid;

                $invoice->update([
                    'amount_paid' => $paid / 100,
                    'amount_due' => $due / 100,
                    'fully_paid_at' => $due <= 0 ? Carbon::now() : null,
                ]);

                return [$invoice, $amount, $paid, $due];
            });
        } catch (ValidationException $e) {
            // The drawer's balance is out of date, so bring it up to what
            // the lock read before the user tries again.
            $this->syncBalance();
            $this->error(collect($e->errors())->flatten()->first());

            return;
        }

        $this->invoice = $invoice;
        $this->amount_paid = $paid / 100;
        $this->amount_due = $due / 100;

        // A part payment leaves the invoice open, so say what is still due
        // rather than that it is paid.
        if ($due > 0) {
            $this->success(ucfirst(trans('laravel-crm::lang.invoice_payment_recorded', [
                'amount' => money($amount, $invoice->currency),
                'due' => money($due, $invoice->currency),
            ])));
        } else {
            $this->success(ucfirst(trans('laravel-crm::lang.invoice_paid')));
        }

        // Ready for the next part payment: emptying the field here left the
        // drawer blank when it was opened again.
        $this->amount = $due > 0 ? $due / 100 : null;

        $this->showPayInvoice = false;

        // The show page's header (Paid badge, Pay button) is another
        // component and would otherwise wait for a reload.
        $this->dispatch('refreshInvoice');
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

    /**
     * Reloads the invoice and sets the paid and due figures from it, with
     * the amount field filled in with what is still due.
     */
    private function syncBalance(): void
    {
        $this->invoice = $this->invoice->fresh() ?? $this->invoice;

        $due = $this->outstandingCents();

        $this->amount_due = $due / 100;
        $this->amount_paid = ((int) $this->invoice->getRawOriginal('amount_paid')) / 100;
        $this->amount = $due > 0 ? $due / 100 : null;
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-pay');
    }
}
