<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveMeeting extends Component
{
    use AuthorizesRequests;
    use HasGlobalSettings;
    use NotifyToast;

    private $settingService;

    public $meeting;

    public $editMode = false;

    public $name;

    public $description;

    public $start_at;

    public $finish_at;

    public $guests = [];

    public $location;

    public $showRelated = false;

    public $view;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount(Meeting $meeting, $view = 'meeting')
    {
        $this->meeting = $meeting;
        $this->name = $meeting->name;
        $this->description = $meeting->description;
        $this->start_at = ($meeting->start_at) ? $meeting->start_at->format($this->dateFormat().' H:i') : null;
        $this->finish_at = ($meeting->finish_at) ? $meeting->finish_at->format($this->dateFormat().' H:i') : null;
        $this->guests = $meeting->contacts()->pluck('entityable_id')->toArray();
        $this->location = $meeting->location;

        if ($this->settingService->get('show_related_activity')->value == 1) {
            $this->showRelated = true;
        }

        $this->view = $view;
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'name' => 'required',
            'description' => 'nullable',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'guests' => 'nullable',
            'location' => 'nullable',
        ];
    }

    public function update()
    {
        $this->authorize('update', $this->meeting);

        $this->validate();
        $this->meeting->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'location' => $this->location,
        ]);

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $this->meeting->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);

                foreach ($this->meeting->contacts as $contact) {
                    if (! in_array($contact->entityable_id, $this->guests)) {
                        $contact->delete();
                    }
                }
            }
        }

        $this->toggleEditMode();
        $this->emit('refreshComponent');
        $this->notify(
            ucfirst(trans('laravel-crm::lang.meeting_updated'))
        );
    }

    public function delete()
    {
        $this->authorize('delete', $this->meeting);

        $this->meeting->delete();

        $this->emit('meetingDeleted');
        $this->notify(
            ucfirst(trans('laravel-crm::lang.meeting_deleted'))
        );
    }

    public function toggleEditMode()
    {
        $this->editMode = ! $this->editMode;

        $this->dispatchBrowserEvent('meetingEditModeToggled');
    }

    public function render()
    {
        return view('laravel-crm::livewire.components.'.$this->view);
    }
}
