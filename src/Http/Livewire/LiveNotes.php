<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveNotes extends Component
{
    use NotifyToast;
    
    public $model;
    public $notes;
    public $content;
    public $noted_at;

    protected $listeners = ['noteDeleted' => 'getNotes'];

    public function mount($model)
    {
        $this->model = $model;
        $this->getNotes();
    }

    public function create()
    {
        $data = $this->validate([
            'content' => 'required',
        ]);
        
        $note = $this->model->notes()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'content' => $data['content'],
            'noted_at' => $this->noted_at,
        ]);
        
        // Add to any upstream related models
        if ($this->model instanceof Person) {
            if ($this->model->organisation) {
                $this->model->organisation->notes()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $data['content'],
                    'noted_at' => $this->noted_at,
                    'related_note_id' => $note->id,
                ]);
            }
        }
        
        if ($this->model instanceof Organisation || $this->model instanceof Person) {
            foreach (Contact::where([
                'entityable_type' => $this->model->getMorphClass(),
                'entityable_id' => $this->model->id,
            ])->get() as $contact) {
                $contact->contactable->notes()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $data['content'],
                    'noted_at' => $this->noted_at,
                    'related_note_id' => $note->id,
                ]);
            }
        }

        $this->notify(
            'Note created',
        );

        $this->resetFields();
    }
    
    public function getNotes()
    {
        $this->notes = $this->model->notes()->latest()->get();
    }

    private function resetFields()
    {
        $this->reset('content', 'noted_at');
        $this->getNotes();
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.notes');
    }
}
