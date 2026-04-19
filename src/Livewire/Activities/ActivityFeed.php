<?php

namespace VentureDrake\LaravelCrm\Livewire\Activities;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class ActivityFeed extends Component
{
    use ResetsPaginationWhenPropsChanges, WithPagination;

    #[Url]
    public string $scope = 'mine';

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
        $this->resetPage();
    }

    #[Computed]
    public function activities()
    {
        $query = Activity::latest();

        if ($this->scope === 'mine') {
            $query->where('causeable_id', auth()->id())
                ->where('causeable_type', auth()->user()->getMorphClass());
        }

        return $query->paginate(25);
    }

    public function render()
    {
        return view('laravel-crm::livewire.activities.activity-feed');
    }
}
