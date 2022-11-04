<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveTasks extends Component
{
    use NotifyToast;
    
    public $model;
    public $tasks;
    public $pinned;
    public $content;
    public $taskd_at;
    public $showForm = false;

    protected $listeners = [
        'addTaskActivity' => 'addTaskToggle',
        'taskDeleted' => 'getTasks',
        'taskPinned' => 'getTasks',
        'taskUnpinned' => 'getTasks',
    ];

    public function mount($model, $pinned = false)
    {
        $this->model = $model;
        $this->pinned = $pinned;
        $this->getTasks();
        
        if ($this->tasks->count() < 1) {
            $this->showForm = true;
        }
    }

    public function create()
    {
        $data = $this->validate([
            'content' => 'required',
        ]);
        
        $task = $this->model->tasks()->create([
            'content' => $data['content'],
            'taskd_at' => $this->taskd_at,
        ]);
        
        // Add to any upstream related models
        if ($this->model instanceof Person) {
            if ($this->model->organisation) {
                $this->model->organisation->tasks()->create([
                    'content' => $data['content'],
                    'taskd_at' => $this->taskd_at,
                    'related_task_id' => $task->id,
                ]);
            }
        }
        
        if ($this->model instanceof Organisation || $this->model instanceof Person) {
            foreach (Contact::where([
                'entityable_type' => $this->model->getMorphClass(),
                'entityable_id' => $this->model->id,
            ])->get() as $contact) {
                $contact->contactable->tasks()->create([
                    'content' => $data['content'],
                    'taskd_at' => $this->taskd_at,
                    'related_task_id' => $task->id,
                ]);
            }
        }

        $this->notify(
            'Task created',
        );

        $this->resetFields();
    }
    
    public function getTasks()
    {
        if ($this->pinned) {
            $this->tasks = $this->model->tasks()->where('pinned', 1)->latest()->get();
        } else {
            $this->tasks = $this->model->tasks()->latest()->get();
        }
    }
    
    public function addTaskToggle()
    {
        $this->showForm = ! $this->showForm;

        $this->dispatchBrowserEvent('taskEditModeToggled');
    }

    private function resetFields()
    {
        $this->reset('content', 'taskd_at');
        $this->getTasks();
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.tasks');
    }
}
