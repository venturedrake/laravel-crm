<?php

namespace VentureDrake\LaravelCrm\Livewire\Files;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\File;

class FileItem extends Component
{
    use Toast;

    public File $file;

    public bool $related = false;

    public function mount(File $file, bool $related = false): void
    {
        $this->file = $file;
        $this->related = $related;
    }

    public function download(): mixed
    {
        return Storage::disk($this->file->disk)->download($this->file->file, $this->file->name);
    }

    public function delete(): void
    {
        $this->file->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.file_deleted')));

        $this->dispatch('file-updated');
        $this->dispatch('activity-logged');
    }

    public function render()
    {
        return view('laravel-crm::livewire.files.file-item');
    }
}
