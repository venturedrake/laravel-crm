<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;

class RelatedOrganizations extends Component
{
    use HasOrganizationSuggest;

    public $showAddRelatedOrganization = false;

    public $model = null;

    public array $data = [];

    public $organization_id;

    public $organization_name;

    public function add()
    {
        // TBC
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
