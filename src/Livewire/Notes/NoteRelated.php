<?php

namespace VentureDrake\LaravelCrm\Livewire\Notes;

use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Note;

class NoteRelated extends Component
{
    use Toast;

    public $model = null;

    public $notes = [];

    public $pinned = false;

    public $content;

    public $noted_at;

    public $showForm = false;

    public array $data = [];

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
            'causeable_type' => auth()->user()->getMorphClass(),
            'causeable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $note->getMorphClass(),
            'recordable_id' => $note->id,
        ]);

        $this->dispatch('note-added');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_created'))
        );

        $this->resetFields();
    }

    #[On('note-updated-pin')]
    #[On('note-added')]
    #[On('note-updated')]
    public function getNotes()
    {
        $relatedIds = [];

        if ($this->pinned) {
            $this->notes = $this->model->notes()->where('pinned', 1)->latest()->get();
        } else {
            $noteIds = [];

            foreach ($this->model->notes()->latest()->get() as $note) {
                $noteIds[] = $note->id;
            }

            if (app('laravel-crm.settings')->get('show_related_activity') == 1) {
                if (method_exists($this->model, 'contacts')) {
                    foreach ($this->model->contacts as $contact) {
                        foreach ($contact->entityable->notes()->latest()->get() as $note) {
                            $noteIds[] = $note->id;
                            $relatedIds[] = $note->id;
                        }
                    }
                }

                if (method_exists($this->model, 'organization') && $this->model->organization) {
                    foreach ($this->model->organization->notes()->latest()->get() as $note) {
                        $noteIds[] = $note->id;
                        $relatedIds[] = $note->id;
                    }
                }

                if (method_exists($this->model, 'person') && $this->model->person) {
                    foreach ($this->model->person->notes()->latest()->get() as $note) {
                        $noteIds[] = $note->id;
                        $relatedIds[] = $note->id;
                    }
                }
            }

            if (count($noteIds) > 0) {
                $this->notes = Note::whereIn('id', $noteIds)->latest()->get();
            }
        }

        foreach ($this->notes as $note) {
            $this->data[$note->id] = [
                'related' => in_array($note->id, $relatedIds),
            ];
        }
    }

    private function resetFields()
    {
        $this->reset('content', 'noted_at');
    }

    public function render()
    {
        $this->getNotes();

        return view('laravel-crm::livewire.notes.note-related');
    }
}
