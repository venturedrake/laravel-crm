<?php

namespace VentureDrake\LaravelCrm\Http\Livewire\Components;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class LiveFile extends Component
{
    use NotifyToast;

    private $settingService;
    public $showRelated = false;
    public $file;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount(File $file)
    {
        $this->file = $file;

        if($this->settingService->get('show_related_activity')->value == 1) {
            $this->showRelated = true;
        }
    }

    public function download()
    {
        return Storage::disk($this->file->disk)->download($this->file->file, $this->file->name);
    }

    public function delete()
    {
        $this->file->delete();

        $this->emit('fileDeleted');
        $this->notify(
            'File deleted.'
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.components.file');
    }
}
