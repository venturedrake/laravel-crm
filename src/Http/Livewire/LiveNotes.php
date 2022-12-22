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
    public $pinned;
    public $content;
    public $noted_at;
    public $showForm = false;

    protected $listeners = [
        'addNoteActivity' => 'addNoteOn',
        'noteDeleted' => 'getNotes',
        'notePinned' => 'getNotes',
        'noteUnpinned' => 'getNotes',
    ];

    public function mount($model, $pinned = false)
    {
        $this->model = $model;
        $this->pinned = $pinned;
        $this->getNotes();

        if ($this->notes->count() < 1) {
            $this->showForm = true;
        }
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

        $this->model->activities()->create([
            'causable_type' => auth()->user()->getMorphClass(),
            'causable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $note->getMorphClass(),
            'recordable_id' => $note->id,
        ]);

        // Add to any upstream related models
        if ($this->model instanceof Person) {
            if ($this->model->organisation) {
                $note = $this->model->organisation->notes()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $data['content'],
                    'noted_at' => $this->noted_at,
                    'related_note_id' => $note->id,
                ]);

                $this->model->activities()->create([
                    'causable_type' => auth()->user()->getMorphClass(),
                    'causable_id' => auth()->user()->id,
                    'timelineable_type' => $this->model->organisation->getMorphClass(),
                    'timelineable_id' => $this->model->organisation->id,
                    'recordable_type' => $note->getMorphClass(),
                    'recordable_id' => $note->id,
                ]);
            }
        }

        if ($this->model instanceof Organisation || $this->model instanceof Person) {
            foreach (Contact::where([
                'entityable_type' => $this->model->getMorphClass(),
                'entityable_id' => $this->model->id,
            ])->get() as $contact) {
                $note = $contact->contactable->notes()->create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $data['content'],
                    'noted_at' => $this->noted_at,
                    'related_note_id' => $note->id,
                ]);

                $this->model->activities()->create([
                    'causable_type' => auth()->user()->getMorphClass(),
                    'causable_id' => auth()->user()->id,
                    'timelineable_type' => $contact->contactable->getMorphClass(),
                    'timelineable_id' => $contact->contactable->id,
                    'recordable_type' => $note->getMorphClass(),
                    'recordable_id' => $note->id,
                ]);
            }
        }

        $this->emit('noteAdded');

        $this->notify(
            'Note created',
        );

        $this->resetFields();
    }

    public function getNotes()
    {
        if ($this->pinned) {
            $this->notes = $this->model->notes()->where('pinned', 1)->latest()->get();
        } else {
            $this->notes = $this->model->notes()->latest()->get();
        }

        $this->emit('refreshActivities');
    }

    public function addNoteToggle()
    {
        $this->showForm = ! $this->showForm;

        $this->dispatchBrowserEvent('noteEditModeToggled');
    }

    public function addNoteOn()
    {
        $this->showForm = true;

        $this->dispatchBrowserEvent('noteAddOn');
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
