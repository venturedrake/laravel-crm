<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Attributes\Modelable;
use Livewire\Component;

class ModelPhones extends Component
{
    public $model = null;

    #[Modelable]
    public array $phones = [];

    public array $phoneTypes = [];

    public function mount()
    {
        $this->phoneTypes = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes();

        /*if (! $this->model) {
            $this->add();
        }*/
    }

    public function phones()
    {
        //
    }

    public function add()
    {
        $this->phones[] = [
            'id' => null,
            'number' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function delete($index)
    {
        unset($this->phones[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-phones');
    }
}
