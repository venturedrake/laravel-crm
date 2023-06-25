<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveTasks extends Component
{
    use NotifyToast;

    private $settingService;
    public $model;
    public $tasks;
    public $name;
    public $description;
    public $due_at;
    public $showForm = false;

    protected $listeners = [
        'addTaskActivity' => 'addTaskOn',
        'taskDeleted' => 'getTasks',
        'taskCompleted' => 'getTasks',
     ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($model)
    {
        $this->model = $model;
        $this->getTasks();

        if (! $this->tasks || ($this->tasks && $this->tasks->count() < 1)) {
            $this->showForm = true;
        }
    }

    public function create()
    {
        $data = $this->validate([
            'name' => 'required',
            'description' => 'nullable',
            'due_at' => 'nullable',
        ]);

        $task = $this->model->tasks()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'due_at' => $this->due_at,
            'user_owner_id' => auth()->user()->id,
            'user_assigned_id' => auth()->user()->id,
        ]);

        $this->model->activities()->create([
            'causable_type' => auth()->user()->getMorphClass(),
            'causable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $task->getMorphClass(),
            'recordable_id' => $task->id,
        ]);

        $this->emit('taskAdded');

        $this->notify(
            'Task created',
        );

        $this->resetFields();
    }

    public function getTasks()
    {
        $taskIds = [];

        foreach($this->model->tasks()->where('user_assigned_id', auth()->user()->id)->latest()->get() as $task) {
            $taskIds[] = $task->id;
        }

        if($this->settingService->get('show_related_activity')->value == 1 && method_exists($this->model, 'contacts')) {
            foreach($this->model->contacts as $contact) {
                foreach ($contact->entityable->tasks()->where('user_assigned_id', auth()->user()->id)->latest()->get() as $task) {
                    $taskIds[] = $task->id;
                }
            }
        }

        if(count($taskIds) > 0) {
            $this->tasks = Task::whereIn('id', $taskIds)->latest()->get();
        }

        $this->emit('refreshActivities');
    }

    public function addTaskToggle()
    {
        $this->showForm = ! $this->showForm;

        $this->dispatchBrowserEvent('taskEditModeToggled');
    }

    public function addTaskOn()
    {
        $this->showForm = true;

        $this->dispatchBrowserEvent('taskAddOn');
    }

    private function resetFields()
    {
        $this->reset('name', 'description', 'due_at');
        $this->getTasks();
    }

    public function render()
    {
        return view('laravel-crm::livewire.tasks');
    }
}
