<?php

namespace VentureDrake\LaravelCrm\Livewire\Notes;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Note;

class NoteItem extends Component
{
    use Toast;

    public Note $note;

    public bool $related = false;

    public bool $editing = false;

    public string $content = '';

    public ?string $noted_at = null;

    private array $revert = [];

    public function mount(Note $note, bool $related = false): void
    {
        $this->note = $note;
        $this->related = $related;
        $this->content = $note->content ?? '';
        $this->noted_at = $note->noted_at?->toDateTimeString();
    }

    public function edit(): void
    {
        $this->revert = [
            'content' => $this->content,
            'noted_at' => $this->noted_at,
        ];

        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->content = $this->revert['content'];
        $this->noted_at = $this->revert['noted_at'];
        $this->editing = false;
    }

    public function update(): void
    {
        $this->validate([
            'content' => 'required',
        ]);

        $this->note->update([
            'content' => $this->content,
            'noted_at' => $this->noted_at,
        ]);

        $this->dispatch('note-updated');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_updated'))
        );

        $this->editing = false;
    }

    public function pin(): void
    {
        $this->note->update(['pinned' => 1]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_pinned'))
        );

        $this->dispatch('note-updated-pin');
    }

    public function unpin(): void
    {
        $this->note->update(['pinned' => 0]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.note_unpinned'))
        );

        $this->dispatch('note-updated-pin');
    }

    public function delete(): void
    {
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
