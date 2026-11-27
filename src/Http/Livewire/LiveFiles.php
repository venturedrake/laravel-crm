<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Support\AttachmentUpload;

class LiveFiles extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    private $settingService;

    public $model;

    public $files;

    public $file;

    public $random;

    public $showForm = false;

    protected $listeners = [
        'addFileActivity' => 'addFileOn',
        'fileDeleted' => '$refresh',
    ];

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($model)
    {
        $this->model = $model;
        $this->random = rand();

        if (! $this->files || ($this->files && $this->files->count() < 1)) {
            $this->showForm = true;
        }
    }

    public function upload()
    {
        $this->authorize('create', File::class);

        $this->validate([
            'file' => AttachmentUpload::rules(),
        ]);

        $attributes = AttachmentUpload::store($this->file, $this->model, 'file');
        $attributes['external_id'] = Uuid::uuid4()->toString();
        $fileModel = $this->model->files()->create($attributes);

        $this->model->activities()->create([
            'causeable_type' => auth()->user()->getMorphClass(),
            'causeable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $fileModel->getMorphClass(),
            'recordable_id' => $fileModel->id,
        ]);

        $this->dispatch('notifyToast', message: 'File uploaded', level: 'success');

        $this->dispatch('fileUploaded');

        $this->resetFields();
    }

    public function getFiles()
    {
        $fileIds = [];

        foreach ($this->model->files()->latest()->get() as $file) {
            $fileIds[] = $file->id;
        }

        if ($this->settingService->get('show_related_activity') == 1 && method_exists($this->model, 'contacts')) {
            foreach ($this->model->contacts as $contact) {
                foreach ($contact->entityable->files()->latest()->get() as $file) {
                    $fileIds[] = $file->id;
                }
            }
        }

        if (count($fileIds) > 0) {
            $this->files = File::whereIn('id', $fileIds)->latest()->get();
        }

        $this->dispatch('refreshActivities');
    }

    public function addFileToggle()
    {
        $this->showForm = ! $this->showForm;
        $this->dispatch('addFileToggled');
    }

    public function addFileOn()
    {
        $this->showForm = true;
        $this->dispatch('fileAddOn');
    }

    private function resetFields()
    {
        $this->reset('file');
        $this->random = rand();
    }

    public function render()
    {
        $this->getFiles();

        return view('laravel-crm::livewire.files');
    }
}
