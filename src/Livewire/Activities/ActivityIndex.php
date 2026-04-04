<?php

namespace VentureDrake\LaravelCrm\Livewire\Activities;

use Livewire\Component;
use Mary\Traits\Toast;

class ActivityIndex extends Component
{
    use Toast;

    public $model = null;

    public function render()
    {
        return view('laravel-crm::livewire.activities.activity-index');
    }
}
