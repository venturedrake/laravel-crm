<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;

class ModelProducts extends Component
{
    public ?string $creating = null;

    public $model = null;

    public ?string $from;

    public array $products = [];

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;

    public bool $dynamicProducts = false;

    public bool $showCreateProduct = false;

    protected $listeners = [
        'product-created' => '$refresh',
    ];

    public function mount()
    {
        if (! $this->model) {
            $this->add();
        } else {
            switch (class_basename($this->model)) {
                case 'Quote':
                    foreach ($this->model->quoteProducts as $quoteProduct) {
                        $this->products[] = [
                            'quote_product_id' => $quoteProduct->id,
                            'id' => $quoteProduct->product_id,
                            'name' => $quoteProduct->product->name,
                            'quantity' => $quoteProduct->quantity,
                            'unit_price' => $quoteProduct->price / 100,
                            'tax_rate' => $quoteProduct->tax_rate,
                            'tax_amount' => $quoteProduct->tax_amount / 100,
                            'amount' => $quoteProduct->amount / 100,
                            'comments' => $quoteProduct->comments,
                        ];
                    }

                    break;

                case 'Order':
                    foreach ($this->model->orderProducts as $orderProduct) {
                        if ($this->creating == 'Invoice' && $this->from == 'Order') {
                            $quantities = [];
                            $quantityRemaining = $orderProduct->quantity;

                            foreach ($this->model->invoices as $invoice) {
                                if ($invoiceProduct = $invoice->invoiceLines()->where('order_product_id', $orderProduct->id)->first()) {
                                    $quantityRemaining -= $invoiceProduct->quantity;
                                }
                            }

                            for ($i = 0; $i <= $quantityRemaining; $i++) {
                                $quantities[] = [
                                    'id' => $i,
                                    'name' => $i,
                                ];
                            }
                        } elseif ($this->creating == 'Delivery' && $this->from == 'Order') {
                            $quantities = [];
                            $quantityRemaining = $orderProduct->quantity;

                            /*foreach ($this->model->invoices as $invoice) {
                                if ($invoiceProduct = $invoice->invoiceLines()->where('order_product_id', $orderProduct->id)->first()) {
                                    $quantityRemaining -= $invoiceProduct->quantity;
                                }
                            }*/

                            for ($i = 0; $i <= $quantityRemaining; $i++) {
                                $quantities[] = [
                                    'id' => $i,
                                    'name' => $i,
                                ];
                            }
                        } elseif ($this->creating == 'PurchaseOrder' && $this->from == 'Order') {
                            $quantityRemaining = $orderProduct->quantity;

                            /* foreach ($this->model->purchaseOrders as $purchaseOrder) {
                                 if ($purchaseOrderProduct = $purchaseOrder->purchaseOrderLines()->where('purchase_order_product_id', $orderProduct->id)->first()) {
                                     $quantityRemaining -= $purchaseOrderProduct->quantity;
                                 }
                             }*/
                        }

                        $this->products[] = [
                            'order_product_id' => $orderProduct->id,
                            'id' => $orderProduct->product_id,
                            'name' => $orderProduct->product->name,
                            'quantities' => $quantities ?? [],
                            'quantity' => $quantityRemaining ?? $orderProduct->quantity,
                            'unit_price' => $orderProduct->price / 100,
                            'tax_rate' => $orderProduct->tax_rate,
                            'tax_amount' => $orderProduct->tax_amount / 100,
                            'amount' => $orderProduct->amount / 100,
                            'comments' => $orderProduct->comments,
                        ];
                    }
                    break;

                case 'Invoice':
                    foreach ($this->model->invoiceLines as $invoiceLine) {
                        $this->products[] = [
                            'invoice_line_id' => $invoiceLine->id,
                            'id' => $invoiceLine->product_id,
                            'name' => $invoiceLine->product->name,
                            'quantity' => $invoiceLine->quantity,
                            'unit_price' => $invoiceLine->price / 100,
                            'tax_rate' => $invoiceLine->tax_rate,
                            'tax_amount' => $invoiceLine->tax_amount / 100,
                            'amount' => $invoiceLine->amount / 100,
                            'comments' => $invoiceLine->comments,
                        ];
                    }
                    break;

                case 'PurchaseOrder':
                    foreach ($this->model->purchaseOrderLines as $purchaseOrderLine) {
                        $this->products[] = [
                            'purchase_order_line_id' => $purchaseOrderLine->id,
                            'id' => $purchaseOrderLine->product_id,
                            'name' => $purchaseOrderLine->name,
                            'quantity' => $purchaseOrderLine->quantity,
                            'unit_price' => $purchaseOrderLine->price / 100,
                            'tax_rate' => $purchaseOrderLine->tax_rate,
                            'tax_amount' => $purchaseOrderLine->tax_amount / 100,
                            'amount' => $purchaseOrderLine->amount / 100,
                            'comments' => $purchaseOrderLine->comments,
                        ];
                    }
                    break;
            }

            $this->sub_total = $this->model->subtotal / 100;
            $this->tax = $this->model->tax / 100;
            $this->total = $this->model->total / 100;

            $this->dispatch('model-products-updated', products: $this->products, sub_total: $this->sub_total, tax: $this->tax, total: $this->total);
        }

        $this->dynamicProducts = (app('laravel-crm.settings')->get('dynamic_products')) ? true : false;
    }

    public function updatedProducts($value, $key)
    {
        $updating = explode('.', $key);

        $this->sub_total = 0;
        $this->discount = 0;
        $this->tax = 0;
        $this->adjustment = 0;
        $this->total = 0;

        $taxTotal = 0;

        if ($updating[1] == 'id') {
            $product = Product::find($value);

            if ($product) {
                $price = $product->getDefaultPrice()->unit_price ?? 0;
                $quantity = (int) $this->products[$updating[0]]['quantity'] ?? 1;
                $this->products[$updating[0]]['unit_price'] = ($price / 100);

                if ($product && $product->taxRate) {
                    $taxRate = $product->taxRate->rate;
                } elseif ($product && $product->tax_rate) {
                    $taxRate = $product->tax_rate;
                } elseif ($taxRate = TaxRate::where('default', 1)->first()) {
                    $taxRate = $taxRate->rate;
                } else {
                    $taxRate = app('laravel-crm.settings')->get('tax_rate', 0);
                }

                $this->products[$updating[0]]['tax_rate'] = $taxRate;

                $tax = (($price / 100) * $quantity) * ((int) $taxRate / 100);
                $this->products[$updating[0]]['tax_amount'] = round($tax, 2);
                $this->products[$updating[0]]['amount'] = ($price / 100) * $quantity;
            }
        } else {
            if ($product = Product::find($this->products[$updating[0]]['id'])) {
                $quantity = (int) $this->products[$updating[0]]['quantity'] ?? 1;

                if ($product && $product->taxRate) {
                    $taxRate = $product->taxRate->rate;
                } elseif ($product && $product->tax_rate) {
                    $taxRate = $product->tax_rate;
                } elseif ($taxRate = TaxRate::where('default', 1)->first()) {
                    $taxRate = $taxRate->rate;
                } else {
                    $taxRate = app('laravel-crm.settings')->get('tax_rate', 0);
                }

                $this->products[$updating[0]]['tax_rate'] = $taxRate;

                $tax = (($this->products[$updating[0]]['unit_price']) * $quantity) * ((int) $taxRate / 100);
                $this->products[$updating[0]]['tax_amount'] = round($tax, 2);
                $this->products[$updating[0]]['amount'] = $this->products[$updating[0]]['unit_price'] * $quantity;
            }
        }

        foreach ($this->products as $key => $value) {
            $this->sub_total += $value['amount'];
            $this->tax += $value['tax_amount'];
        }

        $this->total = round($this->sub_total + $this->tax, 2);
        $this->sub_total = round($this->sub_total, 2);
        $this->tax = round($this->tax, 2);

        $this->dispatch('model-products-updated', products: $this->products, sub_total: $this->sub_total, tax: $this->tax, total: $this->total);
    }

    public function add()
    {
        $this->products[] = [
            'id' => null,
            'name' => null,
            'quantity' => 1,
            'unit_price' => null,
            'tax_rate' => null,
            'tax_amount' => null,
            'amount' => null,
            'comments' => null,
        ];

        $this->dispatch('model-products-updated', products: $this->products);
    }

    public function remove($index)
    {
        unset($this->products[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-products');
    }
}
