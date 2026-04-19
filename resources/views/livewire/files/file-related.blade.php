<div class="grid gap-5">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.add_file')) }}" separator>
        <div class="grid gap-4" wire:key="file-upload">
            <x-mary-file wire:model="uploadedFile" label="{{ ucfirst(__('laravel-crm::lang.choose_file')) }}" />
            <div wire:loading wire:target="uploadedFile" class="text-sm text-base-content/60">
                {{ ucfirst(__('laravel-crm::lang.upload')) }}ing...
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

