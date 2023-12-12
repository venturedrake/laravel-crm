<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveInvoiceLines extends Component
{
    use NotifyToast;

    private $settingService;

    public $invoice;

    public $invoiceLines;

    public $order_product_id;
    public $invoice_line_id;

    public $product_id;

    public $name;

    public $order_quantities;

    public $price;

    public $quantity;

    public $amount;

    public $comments;

    public $inputs = [];

    public $i = 0;

    public $sub_total = 0;

    public $tax = 0;

    public $total = 0;

    public $fromOrder;

    protected $listeners = ['loadInvoiceLineDefault'];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($invoice, $invoiceLines, $old = null, $fromOrder = false)
    {
        $this->invoice = $invoice;
        $this->invoiceLines = $invoiceLines;
        $this->old = $old;
        $this->fromOrder = $fromOrder;

        if ($this->old) {
            foreach ($this->old as $old) {
                $this->add($this->i);
                $this->order_product_id[$this->i] = $old['order_product_id'] ?? null;
                $this->invoice_line_id[$this->i] = $old['invoice_line_id'] ?? null;
                $this->product_id[$this->i] = $old['product_id'] ?? null;
                $this->name[$this->i] = Product::find($old['product_id'])->name ?? null;
                $this->quantity[$this->i] = $old['quantity'] ?? null;

                if ($this->fromOrder) {
                    foreach ($this->invoiceLines as $invoiceLine) {
                        for ($i = 0; $i <= $this->getRemainOrderQuantity($invoiceLine); $i++) {
                            $this->order_quantities[$this->i][$i] = $i;
                        }
                    }
                }

                $this->price[$this->i] = $old['price'] ?? null;
                $this->amount[$this->i] = $old['amount'] ?? null;
                $this->comments[$this->i] = $old['comments'] ?? null;
            }
        } elseif ($this->invoiceLines && $this->invoiceLines->count() > 0) {
            foreach ($this->invoiceLines as $invoiceLine) {
                $this->add($this->i);

                if ($this->fromOrder) {
                    $this->order_product_id[$this->i] = $invoiceLine->id;
                } else {
                    $this->invoice_line_id[$this->i] = $invoiceLine->id;
                }

                $this->product_id[$this->i] = $invoiceLine->product->id ?? null;
                $this->name[$this->i] = $invoiceLine->product->name ?? null;
                $this->quantity[$this->i] = $invoiceLine->quantity;

                if ($this->fromOrder) {
                    for ($i = 0; $i <= $this->getRemainOrderQuantity($invoiceLine); $i++) {
                        $this->order_quantities[$this->i][$i] = $i;
                        $this->quantity[$this->i] = $i;
                    }
                }

                $this->price[$this->i] = $invoiceLine->price / 100;
                $this->amount[$this->i] = $invoiceLine->amount / 100;
                $this->comments[$this->i] = $invoiceLine->comments;
            }
        } elseif (! $this->fromOrder) {
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
            if (isset($this->product_id[$i])) {
                if($product = \VentureDrake\LaravelCrm\Models\Product::find($this->product_id[$i])) {
                    $taxRate = $product->taxRate->rate ?? $product->tax_rate ?? 0;
                } elseif($taxRate = TaxRate::where('default', 1)->first()) {
                    $taxRate = $taxRate->rate;
                } elseif($taxRate = $this->settingService->get('tax_rate')) {
                    $taxRate = $taxRate->value;
                } else {
                    $taxRate = 0;
                }

                if (is_numeric($this->price[$i]) && is_numeric($this->quantity[$i])) {
                    $this->amount[$i] = $this->price[$i] * $this->quantity[$i];
                    $this->price[$i] = $this->currencyFormat($this->price[$i]);
                } else {
                    $this->amount[$i] = 0;
                }

                $this->sub_total += $this->amount[$i];
                $this->tax += $this->amount[$i] * ($taxRate / 100);
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

    public function getRemainOrderQuantity($orderProduct)
    {
        $quantity = $orderProduct->quantity;
        foreach ($this->fromOrder->invoices as $invoice) {
            if ($invoiceProduct = $invoice->invoiceLines()->where('order_product_id', $orderProduct->id)->first()) {
                $quantity -= $invoiceProduct->quantity;
            }
        }

        return $quantity;
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoice-lines');
    }
}
