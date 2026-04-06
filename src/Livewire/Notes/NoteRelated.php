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

    public array $revert = [];

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
    public function getNotes()
    {
        if ($this->pinned) {
            $this->notes = $this->model->notes()->where('pinned', 1)->latest()->get();
        } else {
            $noteIds = [];
            $relatedIds = [];

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
            $this->data[$note->id] = array_merge([
                'editing' => false,
            ], $this->data[$note->id] ?? [], [
                'id' => $note->id,
                'content' => $note->content,
                'pinned' => $note->pinned,
                'noted_at' => ($note->noted_at) ? $note->noted_at->toDateTimeString() : null,
                'related' => (in_array($note->id, $relatedIds) ? true : false),
            ]);
        }
    }

    public function edit($id)
    {
        $this->revert[$id] = $this->data[$id];
        $this->data[$id]['editing'] = true;

    }

    public function cancel($id)
    {
        $this->data[$id]['editing'] = false;
        $this->data[$id] = $this->revert[$id];
    }

    public function update($id)
    {
        $this->validate([
            'data.'.$id.'.content' => 'required',
        ]);

        if ($note = $this->model->notes()->find($id)) {
            $note->update([
                'content' => $this->data[$id]['content'],
                'noted_at' => $this->data[$id]['noted_at'],
            ]);
        }

        $this->dispatch('note-updated');

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_updated'))
        );

        $this->data[$id]['editing'] = false;
    }

    public function pin($id)
    {
        if ($note = $this->model->notes()->find($id)) {
            $note->update(['pinned' => 1]);
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_pinned'))
        );

        $this->dispatch('note-updated-pin');
    }

    public function unpin($id)
    {
        if ($note = $this->model->notes()->find($id)) {
            $note->update(['pinned' => 0]);
        }

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_unpinned'))
        );

        $this->dispatch('note-updated-pin');
    }

    public function delete($id)
    {
        if ($note = $this->model->notes()->find($id)) {
            $note->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.note_deleted')));
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
