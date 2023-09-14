<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveDeliveryItems extends Component
{
    use NotifyToast;

    public $delivery;

    public $products;

    public $order_product_id;
    public $delivery_product_id;

    public $product_id;

    public $name;

    public $order_quantities;

    public $delivery_quantities;

    public $quantity;

    public $inputs = [];

    public $i = 0;

    public $removed = [];

    public $fromOrder;

    protected $listeners = ['loadItemDefault'];

    public function mount($delivery, $products, $old = null, $fromOrder = false)
    {
        $this->delivery = $delivery;
        $this->products = $products;
        $this->old = $old;
        $this->fromOrder = $fromOrder;

        if ($this->old) {
            foreach ($this->old as $old) {
                $this->add($this->i);
                $this->order_product_id[$this->i] = $old['order_product_id'] ?? null;
                $this->delivery_product_id[$this->i] = $old['delivery_product_id'] ?? null;
                $this->product_id[$this->i] = $old['product_id'] ?? null;
                $this->name[$this->i] = Product::find($old['product_id'])->name ?? null;
                $this->quantity[$this->i] = $old['quantity'] ?? null;

                if ($this->fromOrder) {
                    foreach ($this->products as $deliveryProduct) {
                        for ($i = 0; $i <= $this->getRemainOrderQuantity($deliveryProduct); $i++) {
                            $this->order_quantities[$this->i][$i] = $i;
                        }
                    }
                } else {
                    foreach ($this->products as $deliveryProduct) {
                        for ($i = 0; $i <= $deliveryProduct->quantity; $i++) {
                            $this->delivery_quantities[$this->i][$i] = $i;
                        }
                    }
                }
            }
        } elseif ($this->products && $this->products->count() > 0) {
            foreach ($this->products as $deliveryProduct) {
                $this->add($this->i);

                if ($this->fromOrder) {
                    $this->order_product_id[$this->i] = $deliveryProduct->id;
                } else {
                    $this->delivery_product_id[$this->i] = $deliveryProduct->id;
                }

                $this->product_id[$this->i] = $deliveryProduct->orderProduct->product->id ?? $deliveryProduct->product->id ?? null;
                $this->name[$this->i] = $deliveryProduct->orderProduct->product->name ?? $deliveryProduct->product->name ?? null;
                $this->quantity[$this->i] = $deliveryProduct->quantity;

                if ($this->fromOrder) {
                    for ($i = 0; $i <= $this->getRemainOrderQuantity($deliveryProduct); $i++) {
                        $this->order_quantities[$this->i][$i] = $i;
                        $this->quantity[$this->i] = $i;
                    }
                } else {
                    for ($i = 0; $i <= $deliveryProduct->quantity; $i++) {
                        $this->delivery_quantities[$this->i][$i] = $i;
                        $this->quantity[$this->i] = $i;
                    }
                }
            }
        } elseif (! $this->fromOrder) {
            $this->add($this->i);
        }
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        $this->quantity[$i] = null;
        array_push($this->inputs, $i);

        $this->dispatchBrowserEvent('addedItem', ['id' => $this->i]);
    }

    public function loadItemDefault($id)
    {
        if ($product = \VentureDrake\LaravelCrm\Models\Product::find($this->product_id[$id])) {
            $this->quantity[$id] = 1;
        } else {
            $this->quantity[$id] = null;
        }
    }

    public function getRemainOrderQuantity($orderProduct)
    {
        $quantity = $orderProduct->quantity;
        foreach ($this->fromOrder->deliveries as $delivery) {
            if ($deliveryProduct = $delivery->deliveryProducts()->where('order_product_id', $orderProduct->id)->first()) {
                $quantity -= $deliveryProduct->quantity;
            }
        }

        return $quantity;
    }

    public function render()
    {
        return view('laravel-crm::livewire.delivery-items');
    }
}
