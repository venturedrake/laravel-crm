<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveNote extends Component
{
    use NotifyToast;
    use HasGlobalSettings;

    private $settingService;
    public $note;
    public $editMode = false;
    public $content;
    public $noted_at;
    public $showRelated = false;
    public $view;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'notePinned' => '$refresh',
        'noteUnpinned' => '$refresh',
    ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount(Note $note, $view = 'note')
    {
        $this->note = $note;
        $this->content = $note->content;
        $this->noted_at = ($note->noted_at) ? $note->noted_at->format($this->dateFormat().' H:i') : null;

        if($this->settingService->get('show_related_activity')->value == 1) {
            $this->showRelated = true;
        }

        $this->view = $view;
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
            'noted_at' => $this->noted_at,
        ]);
        $this->toggleEditMode();
        $this->emit('refreshComponent');
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

        $this->emit('noteUnpinned');
        $this->notify(
            'Note unpinned'
        );
    }

    public function toggleEditMode()
    {
        $this->editMode = ! $this->editMode;

        $this->dispatchBrowserEvent('noteEditModeToggled');
    }

    public function render()
    {
        return view('laravel-crm::livewire.components.'.$this->view);
    }
}
