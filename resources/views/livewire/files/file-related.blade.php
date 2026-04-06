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
            <x-mary-card>
                <div class="grid gap-3">
                    <div class="flex justify-between items-start">
                        <div class="font-bold text-lg">
                            <a href="#" wire:click.prevent="download({{ $file->id }})" class="link link-hover link-primary">{{ $file->name }}</a>
                            <div class="flex flex-row gap-1 flex-wrap mt-1">
                                @if($file->mime)
                                    <x-mary-badge value="{{ $file->mime }}" class="badge-soft badge-sm" />
                                @endif
                                @if($file->filesize)
                                    @php
                                        $size = (int) $file->filesize;
                                        if ($size >= 1024 * 1024) {
                                            $formattedSize = round($size / (1024 * 1024), 2) . ' MB';
                                        } elseif ($size >= 1024) {
                                            $formattedSize = round($size / 1024, 2) . ' KB';
                                        } else {
                                            $formattedSize = $size . ' B';
                                        }
                                    @endphp
                                    <x-mary-badge value="{{ $formattedSize }}" class="badge-soft badge-sm" />
                                @endif
                            </div>
                            @if(in_array($file->id, $relatedFileIds))
                                <div class="flex flex-row gap-2 mt-1">
                                    @if(class_basename($file->fileable->getMorphClass()) == 'Person')
                                        <x-mary-icon name="fas.user-circle" class="text-sm" />
                                        <span class="text-sm">
                                            <a href="{{ route('laravel-crm.people.show', $file->fileable) }}" class="link link-hover link-primary">{{ $file->fileable->name }}</a>
                                        </span>
                                    @elseif(class_basename($file->fileable->getMorphClass()) == 'Organization')
                                        <x-mary-icon name="fas.building" class="text-sm" />
                                        <span class="text-sm">
                                            <a href="{{ route('laravel-crm.organizations.show', $file->fileable) }}" class="link link-hover link-primary">{{ $file->fileable->name }}</a>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <x-mary-dropdown right top>
                            <x-slot:trigger>
                                <x-mary-icon name="o-ellipsis-horizontal" />
                            </x-slot:trigger>
                            <x-mary-menu-item wire:click="download({{ $file->id }})" title="{{ ucfirst(__('laravel-crm::lang.download')) }}" />
                            <x-mary-menu-item onclick="modalDeleteFile{{ $file->id }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
                        </x-mary-dropdown>
                    </div>

                    <div class="text-sm text-base-content/60">
                        {{ $file->created_at->diffForHumans() }}
                        @if($file->createdByUser)
                            &mdash; {{ $file->createdByUser->name }}
                        @endif
                    </div>

                    <x-crm-delete-confirm model="file" id="{{ $file->id }}" />
                </div>
            </x-mary-card>
        @endforeach
    @endif
</div>

