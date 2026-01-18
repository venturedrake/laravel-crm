<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;

class RelatedOrganizations extends Component
{
    public $model = null;

    public array $data = [];

    public function add()
    {
        //
    }

    public function remove($index)
    {
        unset($this->data[$index]);
    }

    public function render()
    {
        return view('laravel-crm::livewire.related-organizations');
    }
}
