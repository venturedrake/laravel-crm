<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\TaxRate;

class ModelProducts extends Component
{
    public $model = null;

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
                    foreach ($this->model->quoteProducts as $product) {
                        $this->products[] = [
                            'quote_product_id' => $product->id,
                            'id' => $product->product_id,
                            'name' => $product->name,
                            'quantity' => $product->quantity,
                            'unit_price' => $product->price / 100,
                            'tax_rate' => $product->tax_rate,
                            'tax_amount' => $product->tax_amount / 100,
                            'amount' => $product->amount / 100,
                            'comments' => $product->comments,
                        ];
                    }
                    break;

                case 'Order':
                    foreach ($this->model->orderProducts as $product) {
                        $this->products[] = [
                            'order_product_id' => $product->id,
                            'id' => $product->product_id,
                            'name' => $product->name,
                            'quantity' => $product->quantity,
                            'unit_price' => $product->price / 100,
                            'tax_rate' => $product->tax_rate,
                            'tax_amount' => $product->tax_amount / 100,
                            'amount' => $product->amount / 100,
                            'comments' => $product->comments,
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
            $product = \VentureDrake\LaravelCrm\Models\Product::find($value);

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
            if ($product = \VentureDrake\LaravelCrm\Models\Product::find($this->products[$updating[0]]['id'])) {
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
