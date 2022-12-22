<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveActivities extends Component
{
    public $model;
    public $activities;

    protected $listeners = [
        'refreshActivities' => 'getActivities',
    ];

    public function mount($model)
    {
        $this->model = $model;
        $this->getActivities();
    }

    public function getActivities()
    {
        $this->activities = $this->model->activities()->latest()->get();
    }

    public function render()
    {
        return view('laravel-crm::livewire.activities');
    }
}
