<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveLunch extends Component
{
    use NotifyToast;
    use HasGlobalSettings;

    private $settingService;
    public $lunch;
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

    public function mount(Lunch $lunch, $view = 'lunch')
    {
        $this->lunch = $lunch;
        $this->name = $lunch->name;
        $this->description = $lunch->description;
        $this->start_at = ($lunch->start_at) ? $lunch->start_at->format($this->dateFormat().' H:i') : null;
        $this->finish_at = ($lunch->finish_at) ? $lunch->finish_at->format($this->dateFormat().' H:i') : null;
        $this->guests = $lunch->contacts()->pluck('entityable_id')->toArray();
        $this->location = $lunch->location;

        if($this->settingService->get('show_related_activity')->value == 1) {
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
            'name' => "required",
            'description' => "nullable",
            'start_at' => 'required',
            'finish_at' => 'required',
            'guests' => 'nullable',
            'location' => "nullable",
        ];
    }

    public function update()
    {
        $this->validate();
        $this->lunch->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'location' => $this->location,
        ]);

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $this->lunch->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);

                foreach ($this->lunch->contacts as $contact) {
                    if (! in_array($contact->entityable_id, $this->guests)) {
                        $contact->delete();
                    }
                }
            }
        }

        $this->toggleEditMode();
        $this->emit('refreshComponent');
        $this->notify(
            ucfirst(trans('laravel-crm::lang.lunch_updated'))
        );
    }

    public function delete()
    {
        $this->lunch->delete();

        $this->emit('lunchDeleted');
        $this->notify(
            ucfirst(trans('laravel-crm::lang.lunch_deleted'))
        );
    }

    public function toggleEditMode()
    {
        $this->editMode = ! $this->editMode;

        $this->dispatchBrowserEvent('lunchEditModeToggled');
    }

    public function render()
    {
        return view('laravel-crm::livewire.components.'.$this->view);
    }
}
