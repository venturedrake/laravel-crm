<x-mary-card class="border border-base-300 mt-2">
    <div class="grid gap-3">
        <div class="flex justify-between items-start">
            <div class="font-bold text-lg">
                {{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}<br />
                @if($related)
                    <div class="flex flex-row items-center gap-2 mt-1">
                        @if(class_basename($note->noteable->getMorphClass()) == 'Person')
                            <x-mary-icon name="fas.user-circle" class="text-sm" />
                            <span class="text-sm">
                                <a href="{{ route('laravel-crm.people.show', $note->noteable) }}" class="link link-hover link-primary">{{ $note->noteable->name }}</a>
                            </span>
                        @elseif(class_basename($note->noteable->getMorphClass()) == 'Organization')
                            <x-mary-icon name="fas.building" class="text-sm" />
                            <span class="text-sm">
                                <a href="{{ route('laravel-crm.organizations.show', $note->noteable) }}" class="link link-hover link-primary">{{ $note->noteable->name }}</a>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            <x-mary-dropdown right>
                <x-slot:trigger>
                    <x-mary-icon name="o-ellipsis-horizontal" />
                </x-slot:trigger>
                <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                @if($note->pinned == 1)
                    <x-mary-menu-item wire:click="unpin" title="{{ ucfirst(__('laravel-crm::lang.unpin_this_note')) }}" />
                @else
                    <x-mary-menu-item wire:click="pin" title="{{ ucfirst(__('laravel-crm::lang.pin_this_note')) }}" />
                @endif
                <x-mary-menu-item onclick="modalDeleteNoteItem{{ $note->id }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
            </x-mary-dropdown>
        </div>

        @if($editing)
            <x-mary-form wire:submit="update">
                <div class="grid gap-3">
                    <x-mary-textarea wire:model="content" label="{{ ucfirst(__('laravel-crm::lang.note')) }}" rows="5" />
                    <x-mary-datetime wire:model="noted_at" label="{{ ucfirst(__('laravel-crm::lang.noted_at')) }}" type="datetime-local" />
                </div>
                <x-slot:actions>
                    <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                </x-slot:actions>
            </x-mary-form>
        @else
            <div>
                {!! $note->content !!}
            </div>

            @if($note->noted_at)
                <div>
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                </div>
            @endif

            <dialog id="modalDeleteNoteItem{{ $note->id }}" class="modal">
                <div class="modal-box text-left">
                    <h3 class="text-lg font-bold">Delete note?</h3>
                    <p class="py-4">You're about to delete this note. This action cannot be reversed.</p>
                    <div class="modal-action">
                        <form method="dialog">
                            <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button wire:click="delete" class="btn btn-error text-white">{{ ucfirst(__('laravel-crm::lang.delete')) }}</button>
                        </form>
                    </div>
                </div>
            </dialog>
        @endif
    </div>
</x-mary-card>

