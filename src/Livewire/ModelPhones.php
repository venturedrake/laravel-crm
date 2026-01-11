<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ModelPhones extends Component
{
    public $model = null;

    public array $data = [];

    public function mount()
    {
        if (! $this->model) {
            $this->add();
        }
    }

    public function phones()
    {
        //
    }

    public function add()
    {
        $this->data[] = [
            'id' => null,
            'number' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function delete($index)
    {
        unset($this->data[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-phones');
    }
}
