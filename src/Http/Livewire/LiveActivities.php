<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Services\SettingService;

class LiveActivities extends Component
{
    private $settingService;
    public $model;
    public $activities = [];

    protected $listeners = [
        'refreshActivities' => 'getActivities',
    ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($model)
    {
        $this->model = $model;
        $this->getActivities();
    }

    public function getActivities()
    {
        $activityIds = [];

        foreach($this->model->activities()->latest()->get() as $activity) {
            $activityIds[] = $activity->id;
        }

        if($this->settingService->get('show_related_activity')->value == 1 && method_exists($this->model, 'contacts')) {
            foreach($this->model->contacts as $contact) {
                foreach ($contact->entityable->activities()->latest()->get() as $activity) {
                    $activityIds[] = $activity->id;
                }
            }
        }

        if(count($activityIds) > 0) {
            $this->activities = Activity::whereIn('id', $activityIds)->latest()->get();
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.activities');
    }
}
