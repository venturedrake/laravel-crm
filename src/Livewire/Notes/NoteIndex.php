<?php

namespace VentureDrake\LaravelCrm\Livewire\Notes;

use Livewire\Component;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Note;

class NoteIndex extends Component
{
    use Toast;

    public $model = null;

    public $notes = [];

    public $pinned = false;

    public $content;

    public $noted_at;

    public $showForm = false;

    public function save()
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

        $this->dispatch('note-added');

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_created'))
        );

        $this->resetFields();
    }

    public function getNotes()
    {
        if ($this->pinned) {
            $this->notes = $this->model->notes()->where('pinned', 1)->latest()->get();
        } else {
            $noteIds = [];

            foreach ($this->model->notes()->latest()->get() as $note) {
                $noteIds[] = $note->id;
            }

            if (app('laravel-crm.settings')->get('show_related_activity') == 1 && method_exists($this->model, 'contacts')) {
                foreach ($this->model->contacts as $contact) {
                    foreach ($contact->entityable->notes()->latest()->get() as $note) {
                        $noteIds[] = $note->id;
                    }
                }
            }

            if (count($noteIds) > 0) {
                $this->notes = Note::whereIn('id', $noteIds)->latest()->get();
            }
        }
    }

    private function resetFields()
    {
        $this->reset('content', 'noted_at');
    }

    public function render()
    {
        $this->getNotes();

        return view('laravel-crm::livewire.notes.note-index');
    }
}
