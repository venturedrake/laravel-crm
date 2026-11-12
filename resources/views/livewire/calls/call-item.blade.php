{{-- Collapsed to a summary until opened. `expanded` lives on the component,
     so an item stays open when the list around it is refreshed. --}}
<div class="relative mt-2">
    <x-mary-collapse wire:model.live="expanded" class="border border-base-300 bg-base-100">
        <x-slot:heading class="text-lg" style="padding-inline-end: 6rem">
            @php
                // Guests and team members share the contacts relation; one
                // query, split by what each link points at.
                $participants = $call->contacts->filter(fn ($contact) => $contact->entityable);
                $teamMembers = $participants->where('entityable_type', \VentureDrake\LaravelCrm\Models\Call::userMorphClass());
                $guests = $participants->reject(fn ($contact) => $teamMembers->contains('id', $contact->id));
            @endphp
            {{ $call->name }}
            <div class="flex flex-row flex-wrap gap-1 mt-1">
                <x-mary-badge
                    value="{{ ucfirst(__('laravel-crm::lang.'.($call->isOutbound() ? 'call_made' : 'call_received'))) }}"
                    class="badge-sm {{ $call->isOutbound() ? 'badge-primary text-white' : 'badge-secondary text-white' }}" />
                @if($call->start_at)
                    <x-mary-badge value="{{ $call->start_at->format('h:i A') }} on {{ $call->start_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                @endif
                @if($duration = $call->durationLabel())
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.duration')) }} {{ $duration }}" class="badge-soft badge-sm" />
                @endif
            </div>
            {{-- Who the call was with, so the summary says it without opening. --}}
            @if($guests->isNotEmpty() || $teamMembers->isNotEmpty())
                <div class="flex flex-row flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm font-normal">
                    @if($guests->isNotEmpty())
                        <span><x-mary-icon name="fas.user-circle" class="text-sm" /> {{ $guests->map(fn ($contact) => $contact->entityable->name)->implode(', ') }}</span>
                    @endif
                    @if($teamMembers->isNotEmpty())
                        <span><x-mary-icon name="fas.user-tie" class="text-sm" /> {{ $teamMembers->map(fn ($contact) => $contact->entityable->name)->implode(', ') }}</span>
                    @endif
                </div>
            @endif
            @if($related)
                <div class="flex flex-row items-center gap-2 mt-1 relative z-10">
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
        </x-slot:heading>
        <x-slot:content>
            @if($expanded)
                <div class="grid gap-3">
                    @if($editing)
                        <x-mary-form wire:submit="update">
                            <div class="grid gap-3">
                                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <x-mary-datetime wire:model="start_at" label="{{ ucfirst(__('laravel-crm::lang.start_at')) }}" type="datetime-local" />
                                    <x-mary-datetime wire:model="finish_at" label="{{ ucfirst(__('laravel-crm::lang.finish_at')) }}" type="datetime-local" />
                                </div>
                                <x-mary-select
                                    wire:model="direction"
                                    label="{{ ucfirst(__('laravel-crm::lang.call_direction')) }}"
                                    :options="[
                                        ['id' => 'outbound', 'name' => ucfirst(__('laravel-crm::lang.call_made'))],
                                        ['id' => 'inbound', 'name' => ucfirst(__('laravel-crm::lang.call_received'))],
                                    ]" />
                                <x-mary-choices wire:model="guests" label="{{ ucfirst(__('laravel-crm::lang.guests')) }}" :options="$guestOptions" search-function="searchGuests" :min-chars="1" placeholder="Search ..." searchable />
                                <x-mary-choices-offline
                                    wire:model="team_members"
                                    label="{{ ucfirst(__('laravel-crm::lang.team_members')) }}"
                                    :options="$users"
                                    placeholder="Search ..."
                                    searchable />
                                <x-mary-input wire:model="location" label="{{ ucfirst(__('laravel-crm::lang.location')) }}" />
                                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
                            </div>
                            <x-slot:actions>
                                @can('delete crm calls')
                                    <x-mary-button onclick="modalDeleteCallItem{{ $call->id }}.showModal()" label="{{ ucfirst(__('laravel-crm::lang.delete')) }}" class="btn-error text-white" style="margin-inline-end: auto" type="button" />
                                @endcan
                                <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                                @can('edit crm calls')
                                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                                @endcan
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        <hr />
                        <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.guests')) }}</h2>
                        @if($guests->isNotEmpty())
                            <div class="flex flex-row gap-2 flex-wrap mb-1">
                                @foreach($guests as $contact)
                                    <x-mary-icon name="fas.user-circle" class="text-sm" />
                                    <span class="text-sm">
                                        <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}" class="link link-hover link-primary">{{ $contact->entityable->name }}</a>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if($teamMembers->isNotEmpty())
                            <hr />
                            <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.team_members')) }}</h2>
                            <div class="flex flex-row gap-2 flex-wrap mb-1">
                                @foreach($teamMembers as $contact)
                                    <span class="text-sm">
                                        <x-mary-icon name="fas.user-tie" class="text-sm" />
                                        {{ $contact->entityable->name }}
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
                    @endif
                </div>
            @endif
        </x-slot:content>
    </x-mary-collapse>

    {{-- Outside the widget: it clips what overflows it, and a click inside
         its heading would open or close the item. --}}
    <div class="absolute top-3 z-10" style="inset-inline-end: 3rem">
        @can('edit crm calls')
            <x-mary-dropdown right top>
                <x-slot:trigger>
                    <x-mary-icon name="o-ellipsis-horizontal" />
                </x-slot:trigger>
                @can('edit crm calls')
                    <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                @endcan
            </x-mary-dropdown>
        @endcan
    </div>

    @can('delete crm calls')
        @include('laravel-crm::livewire.partials.activity-delete-confirm', ['dialogId' => 'modalDeleteCallItem'.$call->id, 'what' => 'call'])
    @endcan
</div>
