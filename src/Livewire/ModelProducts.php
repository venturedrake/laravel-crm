<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ModelProducts extends Component
{
    public $model = null;

    public array $data = [];

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
