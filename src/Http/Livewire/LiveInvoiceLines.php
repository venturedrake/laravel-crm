<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveInvoiceLines extends Component
{
    use NotifyToast;

    public $invoice;

    public $invoiceLines;

    public $invoice_line_id;

    public $product_id;

    public $name;

    public $price;

    public $quantity;

    public $amount;

    public $inputs = [];

    public $i = 0;

    public $sub_total = 0;

    public $tax = 0;

    public $total = 0;

    protected $listeners = ['loadInvoiceLineDefault'];

    public function mount($invoice, $invoiceLines, $old = null)
    {
        $this->invoice = $invoice;
        $this->invoiceLines = $invoiceLines;
        $this->old = $old;

        if ($this->old) {
            foreach ($this->old as $old) {
                $this->add($this->i);
                $this->invoice_line_id[$this->i] = $old['invoice_line_id'] ?? null;
                $this->product_id[$this->i] = $old['product_id'] ?? null;
                $this->name[$this->i] = Product::find($old['product_id'])->name ?? null;
                $this->quantity[$this->i] = $old['quantity'] ?? null;
                $this->price[$this->i] = $old['price'] ?? null;
                $this->amount[$this->i] = $old['amount'] ?? null;
            }
        } elseif ($this->invoiceLines && $this->invoiceLines->count() > 0) {
            foreach ($this->invoiceLines as $invoiceLine) {
                $this->add($this->i);
                $this->invoice_line_id[$this->i] = $invoiceLine->id;
                $this->product_id[$this->i] = $invoiceLine->product->id ?? null;
                $this->name[$this->i] = $invoiceLine->product->name ?? null;
                $this->quantity[$this->i] = $invoiceLine->quantity;
                $this->price[$this->i] = $invoiceLine->price / 100;
                $this->amount[$this->i] = $invoiceLine->amount / 100;
            }
        } else {
            $this->add($this->i);
        }

        $this->calculateAmounts();
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        $this->price[$i] = null;
        $this->quantity[$i] = null;
        array_push($this->inputs, $i);

        $this->dispatchBrowserEvent('addedItem', ['id' => $this->i]);
    }

    public function loadInvoiceLineDefault($id)
    {
        if ($product = \VentureDrake\LaravelCrm\Models\Product::find($this->product_id[$id])) {
            $this->price[$id] = ($product->getDefaultPrice()->unit_price / 100);
            $this->quantity[$id] = 1;
        } else {
            $this->price[$id] = null;
            $this->quantity[$id] = null;
            $this->amount[$id] = null;
        }
       
        $this->calculateAmounts();
    }

    public function calculateAmounts()
    {
        $this->sub_total = 0;
        $this->tax = 0;
        $this->total = 0;

        for ($i = 1; $i <= $this->i; $i++) {
            if (isset($this->product_id[$i]) && $product = \VentureDrake\LaravelCrm\Models\Product::find($this->product_id[$i])) {
                if (is_numeric($this->price[$i]) && is_numeric($this->quantity[$i])) {
                    $this->amount[$i] = $this->price[$i] * $this->quantity[$i];
                    $this->price[$i] = $this->currencyFormat($this->price[$i]);
                } else {
                    $this->amount[$i] = 0;
                }
                
                $this->sub_total += $this->amount[$i];
                $this->tax += $this->amount[$i] * ($product->tax_rate / 100);
                $this->amount[$i] = $this->currencyFormat($this->amount[$i]);
            }
        }

        $this->total = $this->sub_total + $this->tax;

        $this->sub_total = $this->currencyFormat($this->sub_total);
        $this->tax = $this->currencyFormat($this->tax);
        $this->total = $this->currencyFormat($this->total);
    }

    public function remove($id)
    {
        unset($this->inputs[$id - 1], $this->product_id[$id], $this->name[$id]);

        $this->dispatchBrowserEvent('removedItem', ['id' => $id]);

        $this->calculateAmounts();
    }

    protected function currencyFormat($number)
    {
        return number_format($number, 2, '.', '');
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoice-lines');
    }
}
