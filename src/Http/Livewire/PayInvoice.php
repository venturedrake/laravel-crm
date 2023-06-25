<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class PayInvoice extends Component
{
    use NotifyToast;
    use HasGlobalSettings;

    public $invoice;

    public $amount_paid;

    public $amount_due;

    public function mount($invoice)
    {
        $this->invoice = $invoice;
        $this->amount_due = $invoice->amount_due / 100;
        $this->amount_paid = $invoice->amount_paid / 100;

        if ($this->amount_due > 0) {
            $this->amount_paid = $this->amount_due;
        }
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'amount_paid' => 'required|numeric|size:'. ($this->invoice->amount_due / 100),
        ];
    }

    public function pay()
    {
        $this->validate();

        $this->amount_due = ($this->invoice->amount_due / 100) - ($this->amount_paid + ($this->invoice->amount_paid / 100));

        $this->invoice->update([
            'amount_paid' => $this->amount_paid + ($this->invoice->amount_paid / 100),
            'amount_due' => $this->amount_due,
            'fully_paid_at' => (($this->amount_due == 0) ? Carbon::now()->format($this->dateFormat()) : null),
        ]);

        $this->notify(
            'Invoice paid',
        );

        $this->resetFields();

        $this->dispatchBrowserEvent('invoicePaid');
    }

    private function resetFields()
    {
        $this->reset('amount_paid');
    }

    public function render()
    {
        return view('laravel-crm::livewire.pay-invoice');
    }
}
