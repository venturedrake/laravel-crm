<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveNote extends Component
{
    use NotifyToast;
    
    public $note;
    public $editMode = false;
    public $content;
    public $noted_at;
    
    public function mount(Note $note)
    {
        $this->note = $note;
        $this->content = $note->content;
        $this->noted_at = $note->noted_at;
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'content' => "required",
            'noted_at' => "nullable",
        ];
    }

    public function update()
    {
        $this->validate();
        $this->note->update([
            'content' => $this->content,
            'noted_at' => $this->noted_at ?? null,
        ]);
        $this->toggleEditMode();
        $this->notify(
            'Note updated',
        );
    }

    public function delete()
    {
        $this->note->delete();

        $this->emit('noteDeleted');

        $this->notify(
            'Note deleted.'
        );
    }

    public function pin()
    {
        $this->note->update([
            'pinned' => 1,
        ]);

        $this->emit('notePinned');

        $this->notify(
            'Note pinned'
        );
    }

    public function unpin()
    {
        $this->note->update([
            'pinned' => 0,
        ]);

        $this->emit('notePinned');

        $this->notify(
            'Note unpinned'
        );
    }

    public function toggleEditMode()
    {
        $this->editMode = ! $this->editMode;
        
        $this->emit('noteEditModeToggled');
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.components.note');
    }
}
