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

    protected $listeners = ['loadItemDefault'];
    
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
        
        $this->calculateAmounts();
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        $this->unit_price[$i] = null;
        $this->quantity[$i] = null;
        array_push($this->inputs, $i);
    }
    
    public function loadItemDefault($id)
    {
        $product = \VentureDrake\LaravelCrm\Models\Product::find($this->product_id[$id]);
        $this->unit_price[$id] = ($product->getDefaultPrice()->unit_price / 100);
        $this->quantity[$id] = 1;
        $this->calculateAmounts();
    }

    public function calculateAmounts()
    {
        $this->sub_total = 0;
        $this->total = 0;
        
        for ($i = 1; $i <= $this->i; $i++) {
            $this->amount[$i] = $this->unit_price[$i] * $this->quantity[$i];
            $this->sub_total += $this->amount[$i];
            $this->total += $this->sub_total;
        }
    }
        
    public function render()
    {
        return view('laravel-crm::livewire.quote-items');
    }
}
