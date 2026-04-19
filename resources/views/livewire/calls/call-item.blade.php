<x-mary-card>
    <div class="grid gap-3">
        <div class="flex justify-between items-start">
            <div class="font-bold text-lg">
                {{ $call->name }}
                <div class="flex flex-row gap-1 mt-1">
                    @if($call->start_at)
                        <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.start_at')) }} {{ $call->start_at->format('h:i A') }} on {{ $call->start_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                    @endif
                    @if($call->finish_at)
                        <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.finish_at')) }} {{ $call->finish_at->format('h:i A') }} on {{ $call->finish_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                    @endif
                </div>
                @if($related)
                    <div class="flex flex-row items-center gap-2 mt-1">
                        @if(class_basename($call->callable->getMorphClass()) == 'Person')
                            <x-mary-icon name="fas.user-circle" class="text-sm" />
                            <span class="text-sm">
                                <a href="{{ route('laravel-crm.people.show', $call->callable) }}" class="link link-hover link-primary">{{ $call->callable->name }}</a>
                            </span>
                        @elseif(class_basename($call->callable->getMorphClass()) == 'Organization')
                            <x-mary-icon name="fas.building" class="text-sm" />
                            <span class="text-sm">
                                <a href="{{ route('laravel-crm.organizations.show', $call->callable) }}" class="link link-hover link-primary">{{ $call->callable->name }}</a>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            <x-mary-dropdown right top>
                <x-slot:trigger>
                    <x-mary-icon name="o-ellipsis-horizontal" />
                </x-slot:trigger>
                <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                <x-mary-menu-item onclick="modalDeleteCallItem{{ $call->id }}.showModal()" title="{{ ucfirst(__('laravel-crm::lang.delete')) }}" />
            </x-mary-dropdown>
        </div>

        @if($editing)
            <x-mary-form wire:submit="update">
                <div class="grid gap-3">
                    <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-mary-datetime wire:model="start_at" label="{{ ucfirst(__('laravel-crm::lang.start_at')) }}" type="datetime-local" />
                        <x-mary-datetime wire:model="finish_at" label="{{ ucfirst(__('laravel-crm::lang.finish_at')) }}" type="datetime-local" />
                    </div>
                    <x-mary-choices-offline wire:model="guests" label="{{ ucfirst(__('laravel-crm::lang.guests')) }}" :options="$persons" placeholder="Search ..." searchable />
                    <x-mary-input wire:model="location" label="{{ ucfirst(__('laravel-crm::lang.location')) }}" />
                    <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
                </div>
                <x-slot:actions>
                    <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                </x-slot:actions>
            </x-mary-form>
        @else
            <hr />
            <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.guests')) }}</h2>
            @if(count($call->contacts) > 0)
                <div class="flex flex-row gap-2 flex-wrap mb-1">
                    @foreach($call->contacts as $contact)
                        <x-mary-icon name="fas.user-circle" class="text-sm" />
                        <span class="text-sm">
                            <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}" class="link link-hover link-primary">{{ $contact->entityable->name }}</a>
                        </span>
                    @endforeach
                </div>
            @endif
            <hr />
            <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.location')) }}</h2>
            {{ $call->location }}
            <hr />
            <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.description')) }}</h2>
            {{ $call->description }}

            <dialog id="modalDeleteCallItem{{ $call->id }}" class="modal">
                <div class="modal-box text-left">
                    <h3 class="text-lg font-bold">Delete call?</h3>
                    <p class="py-4">You're about to delete this call. This action cannot be reversed.</p>
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

