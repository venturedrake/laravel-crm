<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveQuoteItems extends Component
{
    use NotifyToast;
    
    public $quote;

    public $products;

    public $product_id;
    
    public $name;
    
    public $unit_price;
    
    public $quantity;
    
    public $amount;

    public $inputs = [];

    public $i = 0;

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;
    
    public function mount($quote, $products, $old = null)
    {
        $this->quote = $quote;
        $this->products = $products;
        $this->old = $old;

        if ($this->old) {
            //
        } elseif ($this->products && $this->products->count() > 0) {
            //
        } else {
            $this->add($this->i);
        }
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        array_push($this->inputs, $i);
    }

    public function calculateAmount($id)
    {
        // This doesn't work, using loop in render()
    }
    
    public function render()
    {
        for ($i = 1; $i <= $this->i; $i++) {
            if (isset($this->unit_price[$i]) && isset($this->quantity[$i])) {
                $this->amount[$i] = $this->unit_price[$i] * $this->quantity[$i];
            }
        }

        return view('laravel-crm::livewire.quote-items');
    }
}
