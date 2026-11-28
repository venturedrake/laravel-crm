<?php

namespace VentureDrake\LaravelCrm\Livewire\Notes;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Note;

class NoteItem extends Component
{
    use AuthorizesRequests, Toast;

    public Note $note;

    public bool $related = false;

    public bool $editing = false;

    /**
     * Whether the item is opened to its full details. Bound to the collapse
     * widget, so it survives the list around the item being refreshed.
     */
    public bool $expanded = false;

    public string $content = '';

    public ?string $noted_at = null;

    public function mount(Note $note, bool $related = false): void
    {
        $this->note = $note;
        $this->related = $related;

        $this->hydrateFromRecord();
    }

    /**
     * Fill the form fields from the stored record.
     *
     * Cancelling restores from the record rather than from a snapshot taken in edit():
     * edit() and cancel() are separate requests, so a snapshot would have to survive the
     * round trip on a public property. Reading the record back is both cheaper and
     * correct when someone else has edited it in the meantime.
     */
    private function hydrateFromRecord(): void
    {
        $this->content = $this->note->content ?? '';
        $this->noted_at = $this->note->noted_at?->toDateTimeString();
    }

    public function edit(): void
    {
        $this->editing = true;
        $this->expanded = true;
    }

    public function cancel(): void
    {
        $this->hydrateFromRecord();
        $this->editing = false;
    }

    /**
     * Another copy of this note on the page was changed (the same note is
     * shown in its own tab and on the timeline): show what was saved. The
     * record itself is re-read on every request; only the form fields need
     * filling again, and not while they are being edited here.
     */
    #[On('note-updated.{note.id}')]
    public function refreshFromRecord(): void
    {
        if (! $this->editing) {
            $this->hydrateFromRecord();
        }
    }

    public function update(): void
    {
        $this->authorize('update', $this->note);

        $this->validate([
            'content' => 'required',
        ]);

        $this->note->update([
            'content' => $this->content,
            'noted_at' => $this->noted_at,
        ]);

        $this->dispatch('note-updated');
        $this->dispatch('note-updated.'.$this->note->id);
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_updated'))
        );

        $this->editing = false;
    }

    public function pin(): void
    {
        $this->authorize('update', $this->note);

        $this->note->update(['pinned' => 1]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_pinned'))
        );

        $this->dispatch('note-updated-pin');
    }

    public function unpin(): void
    {
        $this->authorize('update', $this->note);

        $this->note->update(['pinned' => 0]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_unpinned'))
        );

        $this->dispatch('note-updated-pin');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->note);

        $this->note->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.note_deleted')));

        $this->dispatch('note-updated');
        $this->dispatch('activity-logged');
    }

    public function render()
    {
        return view('laravel-crm::livewire.notes.note-item');
    }
}
