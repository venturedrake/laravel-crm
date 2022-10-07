<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveFiles extends Component
{
    use NotifyToast;
    
    public $model;
    public $files;
    public $content;
    public $filed_at;

    protected $listeners = [
        'fileDeleted' => 'getFiles',
    ];

    public function mount($model)
    {
        $this->model = $model;
        $this->getFiles();
    }

    public function create()
    {
        $data = $this->validate([
            'content' => 'required',
        ]);
        
        $file = $this->model->files()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'content' => $data['content'],
            'filed_at' => $this->filed_at,
        ]);
        
        // Add to any upstream related models
        if ($this->model instanceof Person) {
            if ($this->model->organisation) {
                $this->model->organisation->files()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $data['content'],
                    'filed_at' => $this->filed_at,
                    'related_file_id' => $file->id,
                ]);
            }
        }
        
        if ($this->model instanceof Organisation || $this->model instanceof Person) {
            foreach (Contact::where([
                'entityable_type' => $this->model->getMorphClass(),
                'entityable_id' => $this->model->id,
            ])->get() as $contact) {
                $contact->contactable->files()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $data['content'],
                    'filed_at' => $this->filed_at,
                    'related_file_id' => $file->id,
                ]);
            }
        }

        $this->notify(
            'File created',
        );

        $this->resetFields();
    }
    
    public function getFiles()
    {
        $this->files = $this->model->files()->latest()->get();
    }

    private function resetFields()
    {
        $this->reset('file');
        $this->getFiles();
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.files');
    }
}
