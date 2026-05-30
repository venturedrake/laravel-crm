<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_file')) }}" separator>
        <div class="grid gap-4" wire:key="file-upload"
             x-data="{ uploading: false, progress: 0 }"
             x-on:livewire-upload-start="uploading = true; progress = 0"
             x-on:livewire-upload-finish="uploading = false; progress = 100"
             x-on:livewire-upload-cancel="uploading = false; progress = 0"
             x-on:livewire-upload-error="uploading = false; progress = 0"
             x-on:livewire-upload-progress="progress = $event.detail.progress">
            <x-mary-file wire:model="uploadedFile" label="{{ ucfirst(__('laravel-crm::lang.choose_file')) }}" />
            <div x-show="uploading" class="grid gap-1">
                <div class="flex justify-between text-sm text-base-content/60">
                    <span>{{ ucfirst(__('laravel-crm::lang.upload')) }}ing...</span>
                    <span x-text="progress + '%'"></span>
                </div>
                <progress class="progress progress-primary w-full" x-bind:value="progress" max="100"></progress>
            </div>
            @error('uploadedFile')
                <div class="text-error text-sm">{{ $message }}</div>
            @enderror
            <div>
                <x-mary-button wire:click="save" label="{{ ucfirst(__('laravel-crm::lang.upload')) }}" class="btn-primary text-white" spinner="save" />
            </div>
        </div>
    </x-mary-card>

    @if(count($files) > 0)
        @foreach($files as $file)
            @livewire('crm-file-item', ['file' => $file, 'related' => $data[$file->id]['related']], key('file-item-'.$file->id))
        @endforeach
    @endif
</div>

