<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ModelEmails extends Component
{
    public $model = null;

    public array $data = [];

    public function mount()
    {
        if (! $this->model) {
            $this->add();
        }
    }

    public function emails()
    {
        //
    }

    public function add()
    {
        $this->data[] = [
            'id' => null,
            'address' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function remove($index)
    {
        unset($this->data[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-emails');
    }
}
