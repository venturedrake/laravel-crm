<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveCall extends Component
{
    use NotifyToast;
    use HasGlobalSettings;

    private $settingService;
    public $call;
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

    public function mount(Call $call, $view = 'call')
    {
        $this->call = $call;
        $this->name = $call->name;
        $this->description = $call->description;
        $this->start_at = ($call->start_at) ? $call->start_at->format($this->dateFormat().' H:i') : null;
        $this->finish_at = ($call->finish_at) ? $call->finish_at->format($this->dateFormat().' H:i') : null;
        $this->guests = $call->contacts()->pluck('entityable_id')->toArray();
        $this->location = $call->location;

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
        $this->call->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'location' => $this->location,
        ]);

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $this->call->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);

                foreach ($this->call->contacts as $contact) {
                    if (! in_array($contact->entityable_id, $this->guests)) {
                        $contact->delete();
                    }
                }
            }
        }

        $this->toggleEditMode();
        $this->emit('refreshComponent');
        $this->notify(
            ucfirst(trans('laravel-crm::lang.call_updated'))
        );
    }

    public function delete()
    {
        $this->call->delete();

        $this->emit('callDeleted');
        $this->notify(
            ucfirst(trans('laravel-crm::lang.call_deleted'))
        );
    }

    public function toggleEditMode()
    {
        $this->editMode = ! $this->editMode;

        $this->dispatchBrowserEvent('callEditModeToggled');
    }

    public function render()
    {
        return view('laravel-crm::livewire.components.'.$this->view);
    }
}
