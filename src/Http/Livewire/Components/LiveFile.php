<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveFile extends Component
{
    use NotifyToast;
    
    public $file;
    public $editMode = false;
    public $content;
    public $filed_at;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'filePinned' => '$refresh',
        'fileUnpinned' => '$refresh',
    ];
    
    public function mount(File $file)
    {
        $this->file = $file;
        $this->content = $file->content;
        $this->filed_at = ($file->filed_at) ? $file->filed_at->format('Y/m/d H:i') : null;
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
            'filed_at' => "nullable",
        ];
    }

    public function update()
    {
        $this->validate();
        $this->file->update([
            'content' => $this->content,
            'filed_at' => $this->filed_at,
        ]);
        $this->toggleEditMode();
        $this->emit('refreshComponent');
        $this->notify(
            'File updated',
        );
    }

    public function delete()
    {
        $this->file->delete();

        $this->emit('fileDeleted');
        $this->notify(
            'File deleted.'
        );
    }

    public function pin()
    {
        $this->file->update([
            'pinned' => 1,
        ]);

        $this->emit('filePinned');
        $this->notify(
            'File pinned'
        );
    }

    public function unpin()
    {
        $this->file->update([
            'pinned' => 0,
        ]);

        $this->emit('fileUnpinned');
        $this->notify(
            'File unpinned'
        );
    }

    public function toggleEditMode()
    {
        $this->editMode = ! $this->editMode;
        
        $this->dispatchBrowserEvent('fileEditModeToggled');
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.components.file');
    }
}
