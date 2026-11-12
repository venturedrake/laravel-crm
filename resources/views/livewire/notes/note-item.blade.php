{{-- Collapsed to a summary until opened. `expanded` lives on the component,
     so an item stays open when the list around it is refreshed. --}}
<div class="relative mt-2">
    <x-mary-collapse wire:model.live="expanded" class="border border-base-300 bg-base-100">
        <x-slot:heading class="text-lg" style="padding-inline-end: 6rem">
            {{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}
            @unless($expanded)
                <div class="text-sm font-normal truncate">{{ \Illuminate\Support\Str::limit((string) \Illuminate\Support\Str::of($note->content)->squish(), 140) }}</div>
            @endunless
            @if($related)
                <div class="flex flex-row items-center gap-2 mt-1 relative z-10">
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
        </x-slot:heading>
        <x-slot:content>
            @if($expanded)
                <div class="grid gap-3">
                    @if($editing)
                        <x-mary-form wire:submit="update">
                            <div class="grid gap-3">
                                <x-mary-textarea wire:model="content" label="{{ ucfirst(__('laravel-crm::lang.note')) }}" rows="5" />
                                <x-mary-datetime wire:model="noted_at" label="{{ ucfirst(__('laravel-crm::lang.noted_at')) }}" type="datetime-local" />
                            </div>
                            <x-slot:actions>
                                @can('delete crm notes')
                                    <x-mary-button onclick="modalDeleteNoteItem{{ $note->id }}.showModal()" label="{{ ucfirst(__('laravel-crm::lang.delete')) }}" class="btn-error text-white" style="margin-inline-end: auto" type="button" />
                                @endcan
                                <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                                @can('edit crm notes')
                                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                                @endcan
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        <div>
                            {{-- Typed into a plain textarea: escaped, with its line breaks kept. --}}
                            {!! nl2br(e($note->content)) !!}
                        </div>

                        @if($note->noted_at)
                            <div>
                                <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </x-slot:content>
    </x-mary-collapse>

    {{-- Outside the widget: it clips what overflows it, and a click inside
         its heading would open or close the item. --}}
    <div class="absolute top-3 z-10" style="inset-inline-end: 3rem">
        @can('edit crm notes')
            <x-mary-dropdown right>
                <x-slot:trigger>
                    <x-mary-icon name="o-ellipsis-horizontal" />
                </x-slot:trigger>
                @can('edit crm notes')
                    <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                    @if($note->pinned == 1)
                        <x-mary-menu-item wire:click="unpin" title="{{ ucfirst(__('laravel-crm::lang.unpin_this_note')) }}" />
                    @else
                        <x-mary-menu-item wire:click="pin" title="{{ ucfirst(__('laravel-crm::lang.pin_this_note')) }}" />
                    @endif
                @endcan
            </x-mary-dropdown>
        @endcan
    </div>

    @can('delete crm notes')
        @include('laravel-crm::livewire.partials.activity-delete-confirm', ['dialogId' => 'modalDeleteNoteItem'.$note->id, 'what' => 'note'])
    @endcan
</div>
