<?php

namespace VentureDrake\LaravelCrm\Livewire\Activities;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Activity;

class ActivityIndex extends Component
{
    use Toast;

    public $model = null;

    #[Computed, On('activity-logged')]
    public function activities()
    {
        $activityIds = [];

        foreach ($this->model->activities()->latest()->get() as $activity) {
            $activityIds[] = $activity->id;
        }

        if (app('laravel-crm.settings')->get('show_related_activity') == 1 && method_exists($this->model, 'contacts')) {
            foreach ($this->model->contacts as $contact) {
                foreach ($contact->entityable->activities()->latest()->get() as $activity) {
                    $activityIds[] = $activity->id;
                }
            }
        }

        if (count($activityIds) > 0) {
            return Activity::whereIn('id', $activityIds)->latest()->get();
        }

        return [];
    }

    public function render()
    {
        return view('laravel-crm::livewire.activities.activity-index');
    }
}
