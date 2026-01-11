<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class ModelAddresses extends Component
{
    public $model = null;

    public array $countries = [];

    public array $data = [];

    public function mount()
    {
        $this->countries = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries();

        if (! $this->model) {
            $this->add();
        }
    }

    public function addresses()
    {
        //
    }

    public function add()
    {
        $this->data[] = [
            'id' => null,
            'address_type_id' => null,
            'country' => app('laravel-crm.settings')->get('country', 'United States'),
        ];
    }

    public function remove($index)
    {
        unset($this->data[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-addresses');
    }
}
