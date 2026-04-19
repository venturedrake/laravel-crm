<?php

namespace VentureDrake\LaravelCrm\Livewire\Files;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\File;

class FileRelated extends Component
{
    use Toast;
    use WithFileUploads;

    public $model = null;

    public $files = [];

    public array $data = [];

    public $uploadedFile;

    public function save(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|max:10240',
        ]);

        $disk = config('filesystems.default', 'local');

        $path = $this->uploadedFile->store(
            'laravel-crm/'.strtolower(class_basename($this->model)).'/'.$this->model->id.'/files',
            $disk
        );

        $fileModel = $this->model->files()->create([
            'file' => $path,
            'name' => $this->uploadedFile->getClientOriginalName(),
            'filesize' => $this->uploadedFile->getSize(),
            'mime' => $this->uploadedFile->getMimeType(),
            'disk' => $disk,
        ]);

        $this->model->activities()->create([
            'causeable_type' => auth()->user()->getMorphClass(),
            'causeable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $fileModel->getMorphClass(),
            'recordable_id' => $fileModel->id,
        ]);

        $this->dispatch('file-added');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.file_uploaded'))
        );

        $this->reset('uploadedFile');
    }

    #[On('file-updated')]
    #[On('file-added')]
    public function getFiles(): void
    {
        $fileIds = [];
        $relatedIds = [];

        foreach ($this->model->files()->latest()->get() as $file) {
            $fileIds[] = $file->id;
        }

        if (app('laravel-crm.settings')->get('show_related_activity') == 1) {
            if (method_exists($this->model, 'contacts')) {
                foreach ($this->model->contacts as $contact) {
                    foreach ($contact->entityable->files()->latest()->get() as $file) {
                        $fileIds[] = $file->id;
                        $relatedIds[] = $file->id;
                    }
                }
            }

            if (method_exists($this->model, 'organization') && $this->model->organization) {
                foreach ($this->model->organization->files()->latest()->get() as $file) {
                    $fileIds[] = $file->id;
                    $relatedIds[] = $file->id;
                }
            }

            if (method_exists($this->model, 'person') && $this->model->person) {
                foreach ($this->model->person->files()->latest()->get() as $file) {
                    $fileIds[] = $file->id;
                    $relatedIds[] = $file->id;
                }
            }
        }

        if (count($fileIds) > 0) {
            $this->files = File::whereIn('id', array_unique($fileIds))->latest()->get();
        } else {
            $this->files = collect();
        }

        foreach ($this->files as $file) {
            $this->data[$file->id] = [
                'related' => in_array($file->id, $relatedIds),
            ];
        }
    }

    public function render()
    {
        $this->getFiles();

        return view('laravel-crm::livewire.files.file-related');
    }
}
