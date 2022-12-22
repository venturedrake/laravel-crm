<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveLunch extends Component
{
    use NotifyToast;

    public $lunch;
    public $editMode = false;
    public $name;
    public $description;
    public $start_at;
    public $finish_at;
    public $view;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    public function mount(Lunch $lunch, $view = 'lunch')
    {
        $this->lunch = $lunch;
        $this->name = $lunch->name;
        $this->description = $lunch->description;
        $this->start_at = ($lunch->start_at) ? $lunch->start_at->format('Y/m/d H:i') : null;
        $this->finish_at = ($lunch->finish_at) ? $lunch->finish_at->format('Y/m/d H:i') : null;
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
        ]);
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
