<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveCalls extends Component
{
    use NotifyToast;

    private $settingService;
    public $model;
    public $calls;
    public $name;
    public $description;
    public $start_at;
    public $finish_at;
    public $guests = [];
    public $location;
    public $showForm = false;

    protected $listeners = [
        'addCallActivity' => 'addCallOn',
        'callDeleted' => 'getCalls',
        'callCompleted' => 'getCalls',
     ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($model)
    {
        $this->model = $model;
        $this->getCalls();

        if (! $this->calls || ($this->calls && $this->calls->count() < 1)) {
            $this->showForm = true;
        }
    }

    public function create()
    {
        $data = $this->validate([
            'name' => "required",
            'description' => "nullable",
            'start_at' => 'required',
            'finish_at' => 'required',
            'guests' => 'nullable',
            'location' => "nullable",
        ]);

        $call = $this->model->calls()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'location' => $this->location,
            'user_owner_id' => auth()->user()->id,
            'user_assigned_id' => auth()->user()->id,
        ]);

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $call->contacts()->create([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->model->activities()->create([
            'causable_type' => auth()->user()->getMorphClass(),
            'causable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $call->getMorphClass(),
            'recordable_id' => $call->id,
        ]);

        $this->notify(
            ucfirst(trans('laravel-crm::lang.call_created'))
        );

        $this->resetFields();
    }

    public function getCalls()
    {
        $callIds = [];

        foreach($this->model->calls()->where('user_assigned_id', auth()->user()->id)->latest()->get() as $call) {
            $callIds[] = $call->id;
        }

        if($this->settingService->get('show_related_activity')->value == 1 && method_exists($this->model, 'contacts')) {
            foreach($this->model->contacts as $contact) {
                foreach ($contact->entityable->calls()->where('user_assigned_id', auth()->user()->id)->latest()->get() as $call) {
                    $callIds[] = $call->id;
                }
            }
        }

        if(count($callIds) > 0) {
            $this->calls = Call::whereIn('id', $callIds)->latest()->get();
        }

        $this->emit('refreshActivities');
    }

    public function addCallToggle()
    {
        $this->showForm = ! $this->showForm;

        $this->dispatchBrowserEvent('callEditModeToggled');
    }

    public function addCallOn()
    {
        $this->showForm = true;

        $this->dispatchBrowserEvent('callAddOn');
    }

    private function resetFields()
    {
        $this->reset('name', 'description', 'start_at', 'finish_at', 'guests', 'location');

        $this->dispatchBrowserEvent('callFieldsReset');

        $this->addCallToggle();

        $this->getCalls();
    }

    public function render()
    {
        return view('laravel-crm::livewire.calls');
    }
}
