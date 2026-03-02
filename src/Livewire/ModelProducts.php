<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ModelProducts extends Component
{
    public $model = null;

    public array $data = [];

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;

    public function mount()
    {
        if (! $this->model) {
            $this->add();
        }
    }

    public function products()
    {
        //
    }

    public function add()
    {
        $this->data[] = [
            'id' => null,
            'name' => null,
            'quantity' => null,
            'unit_price' => null,
            'tax_rate' => null,
            'tax_amount' => null,
            'amount' => null,
            'comments' => null,
        ];
    }

    public function remove($index)
    {
        unset($this->data[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-products');
    }
}
