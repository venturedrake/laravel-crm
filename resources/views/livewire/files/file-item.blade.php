<x-mary-card class="border border-base-300 mt-2">
    <div class="grid gap-3">
        <div class="flex justify-between items-start">
            <div class="font-bold text-lg">
                <a href="#" wire:click.prevent="download" class="link link-hover link-primary">{{ $file->name }}</a>
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
                @if($related)
                    <div class="flex flex-row items-center gap-2 mt-1">
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
                <x-mary-menu-item wire:click="download" title="{{ ucfirst(__('laravel-crm::lang.download')) }}" />
                <x-mary-menu-item onclick="modalDeleteFileItem{{ $file->id }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
            </x-mary-dropdown>
        </div>

        <div class="text-sm text-base-content/60">
            {{ $file->created_at->diffForHumans() }}
            @if($file->createdByUser)
                &mdash; {{ $file->createdByUser->name }}
            @endif
        </div>

        <dialog id="modalDeleteFileItem{{ $file->id }}" class="modal">
            <div class="modal-box text-left">
                <h3 class="text-lg font-bold">Delete file?</h3>
                <p class="py-4">You're about to delete this file. This action cannot be reversed.</p>
                <div class="modal-action">
                    <form method="dialog">
                        <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                        <button wire:click="delete" class="btn btn-error text-white">{{ ucfirst(__('laravel-crm::lang.delete')) }}</button>
                    </form>
                </div>
            </div>
        </dialog>
    </div>
</x-mary-card>

