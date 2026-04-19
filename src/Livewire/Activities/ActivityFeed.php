<?php

namespace VentureDrake\LaravelCrm\Livewire\Activities;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class ActivityFeed extends Component
{
    use ResetsPaginationWhenPropsChanges, WithPagination;

    #[Url]
    public string $scope = 'mine';

    #[Url]
    public string $tab = 'all';

    protected array $activityTypes = [
        'notes' => Note::class,
        'tasks' => Task::class,
        'calls' => Call::class,
        'meetings' => Meeting::class,
        'lunches' => Lunch::class,
        'files' => File::class,
    ];

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    protected function scopedQuery()
    {
        $query = Activity::latest();

        if ($this->scope === 'mine') {
            $query->where('causeable_id', auth()->id())
                ->where('causeable_type', auth()->user()->getMorphClass());
        }

        return $query;
    }

    #[Computed]
    public function activities()
    {
        $query = $this->scopedQuery();

        if ($this->tab !== 'all' && isset($this->activityTypes[$this->tab])) {
            $query->where('recordable_type', $this->activityTypes[$this->tab]);
        }

        return $query->paginate(25);
    }

    public function render()
    {
        return view('laravel-crm::livewire.activities.activity-feed');
    }
}
