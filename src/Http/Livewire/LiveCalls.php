<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveCalls extends Component
{
    use NotifyToast;

    public $model;
    public $calls;
    public $name;
    public $description;
    public $start_at;
    public $finish_at;
    public $showForm = false;

    protected $listeners = [
        'addCallActivity' => 'addCallOn',
        'callDeleted' => 'getCalls',
        'callCompleted' => 'getCalls',
     ];

    public function mount($model)
    {
        $this->model = $model;
        $this->getCalls();

        if ($this->calls->count() < 1) {
            $this->showForm = true;
        }
    }

    public function create()
    {
        $data = $this->validate([
            'name' => 'required',
            'description' => 'nullable',
            'start_at' => 'required',
            'finish_at' => 'required',
        ]);

        $call = $this->model->calls()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'user_owner_id' => auth()->user()->id,
            'user_assigned_id' => auth()->user()->id,
        ]);

        $this->model->activities()->create([
            'causable_type' => auth()->user()->getMorphClass(),
            'causable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $call->getMorphClass(),
            'recordable_id' => $call->id,
        ]);

        $this->notify(
            'Call created',
        );

        $this->resetFields();
    }

    public function getCalls()
    {
        $this->calls = $this->model->calls()->where('user_assigned_id', auth()->user()->id)->latest()->get();
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
        $this->reset('name', 'description', 'start_at', 'finish_at');
        $this->getCalls();
    }

    public function render()
    {
        return view('laravel-crm::livewire.calls');
    }
}
