<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveLunches extends Component
{
    use NotifyToast;

    public $model;
    public $lunches;
    public $name;
    public $description;
    public $start_at;
    public $finish_at;
    public $showForm = false;

    protected $listeners = [
        'addLunchActivity' => 'addLunchOn',
        'lunchDeleted' => 'getLunches',
        'lunchCompleted' => 'getLunches',
     ];

    public function mount($model)
    {
        $this->model = $model;
        $this->getLunches();

        if ($this->lunches->count() < 1) {
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

        $lunch = $this->model->lunches()->create([
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
            'recordable_type' => $lunch->getMorphClass(),
            'recordable_id' => $lunch->id,
        ]);

        $this->notify(
            'Lunch created',
        );

        $this->resetFields();
    }

    public function getLunches()
    {
        $this->lunches = $this->model->lunches()->where('user_assigned_id', auth()->user()->id)->latest()->get();
        $this->emit('refreshActivities');
    }

    public function addLunchToggle()
    {
        $this->showForm = ! $this->showForm;

        $this->dispatchBrowserEvent('lunchEditModeToggled');
    }

    public function addLunchOn()
    {
        $this->showForm = true;

        $this->dispatchBrowserEvent('lunchAddOn');
    }

    private function resetFields()
    {
        $this->reset('name', 'description', 'start_at', 'finish_at');
        $this->getLunches();
    }

    public function render()
    {
        return view('laravel-crm::livewire.lunches');
    }
}
