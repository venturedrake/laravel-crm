<?php

namespace VentureDrake\LaravelCrm\Livewire\Activities;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\EmailMessage;
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
        'emails' => EmailMessage::class,
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

    /**
     * The filter tabs, name => language key. Emails is left out for a user
     * who may not see emails.
     *
     * @return array<string, string>
     */
    public function tabs(): array
    {
        $tabs = ['all' => 'all'] + array_combine(array_keys($this->activityTypes), array_keys($this->activityTypes));

        if (! $this->canViewEmails()) {
            unset($tabs['emails']);
        }

        return $tabs;
    }

    protected function canViewEmails(): bool
    {
        return (bool) auth()->user()?->can('viewAny', EmailMessage::class);
    }

    protected function scopedQuery()
    {
        $query = Activity::latest();

        // Email entries carry correspondence: hide them from a user without
        // the permission to view emails.
        if (! $this->canViewEmails()) {
            $query->where(fn ($query) => $query
                ->whereNull('recordable_type')
                ->orWhere('recordable_type', '!=', (new EmailMessage)->getMorphClass()));
        }

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

        if ($this->tab !== 'all' && isset($this->activityTypes[$this->tab]) && isset($this->tabs()[$this->tab])) {
            $query->where('recordable_type', $this->activityTypes[$this->tab]);
        }

        return $query->paginate(25);
    }

    public function render()
    {
        return view('laravel-crm::livewire.activities.activity-feed');
    }
}
