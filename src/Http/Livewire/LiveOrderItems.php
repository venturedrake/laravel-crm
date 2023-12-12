<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveOrderItems extends Component
{
    use NotifyToast;

    private $settingService;

    public $order;

    public $products;

    public $quote_product_id;
    public $order_product_id;

    public $product_id;

    public $name;

    public $unit_price;

    public $quote_quantities;

    public $quantity;

    public $amount;

    public $comments;

    public $inputs = [];

    public $i = 0;

    public $removed = [];

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;

    public $fromQuote;

    protected $listeners = ['loadItemDefault'];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($order, $products, $old = null, $fromQuote = false)
    {
        $this->order = $order;
        $this->products = $products;
        $this->old = $old;
        $this->fromQuote = $fromQuote;

        if ($this->old) {
            foreach ($this->old as $old) {
                $this->add($this->i);
                $this->quote_product_id[$this->i] = $old['quote_product_id'] ?? null;
                $this->order_product_id[$this->i] = $old['order_product_id'] ?? null;
                $this->product_id[$this->i] = $old['product_id'] ?? null;
                $this->name[$this->i] = Product::find($old['product_id'])->name ?? null;
                $this->quantity[$this->i] = $old['quantity'] ?? null;

                if ($this->fromQuote) {
                    foreach ($this->products as $orderProduct) {
                        for ($i = 0; $i <= $this->getRemainQuoteQuantity($orderProduct); $i++) {
                            $this->quote_quantities[$this->i][$i] = $i;
                        }
                    }
                }

                $this->unit_price[$this->i] = $old['unit_price'] ?? null;
                $this->amount[$this->i] = $old['amount'] ?? null;
                $this->comments[$this->i] = $old['comments'] ?? null;
            }
        } elseif ($this->products && $this->products->count() > 0) {
            foreach ($this->products as $orderProduct) {
                $this->add($this->i);

                if ($this->fromQuote) {
                    $this->quote_product_id[$this->i] = $orderProduct->id;
                } else {
                    $this->order_product_id[$this->i] = $orderProduct->id;
                }

                $this->product_id[$this->i] = $orderProduct->product->id ?? null;
                $this->name[$this->i] = $orderProduct->product->name ?? null;
                $this->quantity[$this->i] = $orderProduct->quantity;

                if ($this->fromQuote) {
                    for ($i = 0; $i <= $this->getRemainQuoteQuantity($orderProduct); $i++) {
                        $this->quote_quantities[$this->i][$i] = $i;
                        $this->quantity[$this->i] = $i;
                    }
                }

                $this->unit_price[$this->i] = $orderProduct->price / 100;
                $this->amount[$this->i] = $orderProduct->amount / 100;
                $this->comments[$this->i] = $orderProduct->comments;
            }
        } elseif (! $this->fromQuote) {
            $this->add($this->i);
        }

        $this->calculateAmounts();
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        $this->unit_price[$i] = null;
        $this->quantity[$i] = null;
        array_push($this->inputs, $i);

        $this->dispatchBrowserEvent('addedItem', ['id' => $this->i]);
    }

    public function loadItemDefault($id)
    {
        if ($product = \VentureDrake\LaravelCrm\Models\Product::find($this->product_id[$id])) {
            $this->unit_price[$id] = ($product->getDefaultPrice()->unit_price / 100);
            $this->quantity[$id] = 1;
        } else {
            $this->unit_price[$id] = null;
            $this->quantity[$id] = null;
            $this->amount[$id] = null;
        }

        $this->calculateAmounts();
    }

    public function getRemainQuoteQuantity($quoteProduct)
    {
        $quantity = $quoteProduct->quantity;
        foreach ($this->fromQuote->orders as $order) {
            if ($orderProduct = $order->orderProducts()->where('quote_product_id', $quoteProduct->id)->first()) {
                $quantity -= $orderProduct->quantity;
            }
        }

        return $quantity;
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

                if (is_numeric($this->unit_price[$i]) && is_numeric($this->quantity[$i])) {
                    $this->amount[$i] = $this->unit_price[$i] * $this->quantity[$i];
                    $this->unit_price[$i] = $this->currencyFormat($this->unit_price[$i]);
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
        $this->discount = $this->currencyFormat($this->discount);
        $this->adjustment = $this->currencyFormat($this->adjustment);
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
        return view('laravel-crm::livewire.order-items');
    }
}
