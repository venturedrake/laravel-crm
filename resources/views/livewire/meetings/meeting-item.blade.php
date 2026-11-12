{{-- Collapsed to a summary until opened. `expanded` lives on the component,
     so an item stays open when the list around it is refreshed. --}}
<div class="relative mt-2">
    <x-mary-collapse wire:model.live="expanded" class="border border-base-300 bg-base-100">
        <x-slot:heading class="text-lg" style="padding-inline-end: 6rem">
            {{ $meeting->name }}
            <div class="flex flex-row gap-1 mt-1">
                @if($meeting->start_at)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.start_at')) }} {{ $meeting->start_at->format('h:i A') }} on {{ $meeting->start_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                @endif
                @if($meeting->finish_at)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.finish_at')) }} {{ $meeting->finish_at->format('h:i A') }} on {{ $meeting->finish_at->toFormattedDateString() }}" class="badge-soft badge-sm" />
                @endif
            </div>
            @if($related)
                <div class="flex flex-row items-center gap-2 mt-1 relative z-10">
                    @if(class_basename($meeting->meetingable->getMorphClass()) == 'Person')
                        <x-mary-icon name="fas.user-circle" class="text-sm" />
                        <span class="text-sm">
                            <a href="{{ route('laravel-crm.people.show', $meeting->meetingable) }}" class="link link-hover link-primary">{{ $meeting->meetingable->name }}</a>
                        </span>
                    @elseif(class_basename($meeting->meetingable->getMorphClass()) == 'Organization')
                        <x-mary-icon name="fas.building" class="text-sm" />
                        <span class="text-sm">
                            <a href="{{ route('laravel-crm.organizations.show', $meeting->meetingable) }}" class="link link-hover link-primary">{{ $meeting->meetingable->name }}</a>
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
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{
                                    snap15(el) {
                                        if (!el.value) return;
                                        const d = new Date(el.value);
                                        d.setMinutes(Math.round(d.getMinutes() / 15) * 15, 0, 0);
                                        const p = n => String(n).padStart(2, '0');
                                        el.value = `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
                                        el.dispatchEvent(new Event('input'));
                                    }
                                }">
                                    <x-mary-datetime wire:model="start_at" label="{{ ucfirst(__('laravel-crm::lang.start_at')) }}" type="datetime-local" x-on:change="snap15($event.target)" />
                                    <x-mary-datetime wire:model="finish_at" label="{{ ucfirst(__('laravel-crm::lang.finish_at')) }}" type="datetime-local" x-on:change="snap15($event.target)" />
                                </div>
                                <x-mary-choices wire:model="guests" label="{{ ucfirst(__('laravel-crm::lang.guests')) }}" :options="$guestOptions" search-function="searchGuests" :min-chars="1" placeholder="Search ..." searchable />
                                <x-mary-input wire:model="location" label="{{ ucfirst(__('laravel-crm::lang.location')) }}" />
                                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
                            </div>
                            <x-slot:actions>
                                @can('delete crm meetings')
                                    <x-mary-button onclick="modalDeleteMeetingItem{{ $meeting->id }}.showModal()" label="{{ ucfirst(__('laravel-crm::lang.delete')) }}" class="btn-error text-white" style="margin-inline-end: auto" type="button" />
                                @endcan
                                <x-mary-button wire:click="cancel" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" type="button" />
                                @can('edit crm meetings')
                                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="update" />
                                @endcan
                            </x-slot:actions>
                        </x-mary-form>
                    @else
                        <hr />
                        <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.guests')) }}</h2>
                        @if(count($meeting->contacts) > 0)
                            <div class="flex flex-row gap-2 flex-wrap mb-1">
                                @foreach($meeting->contacts as $contact)
                                    <x-mary-icon name="fas.user-circle" class="text-sm" />
                                    <span class="text-sm">
                                        <a href="{{ route('laravel-crm.people.show', $contact->entityable) }}" class="link link-hover link-primary">{{ $contact->entityable->name }}</a>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <hr />
                        <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.location')) }}</h2>
                        {{ $meeting->location }}
                        <hr />
                        <h2 class="font-bold">{{ ucfirst(__('laravel-crm::lang.description')) }}</h2>
                        {{ $meeting->description }}
                    @endif
                </div>
            @endif
        </x-slot:content>
    </x-mary-collapse>

    {{-- Outside the widget: it clips what overflows it, and a click inside
         its heading would open or close the item. --}}
    <div class="absolute top-3 z-10" style="inset-inline-end: 3rem">
        @can('edit crm meetings')
            <x-mary-dropdown right top>
                <x-slot:trigger>
                    <x-mary-icon name="o-ellipsis-horizontal" />
                </x-slot:trigger>
                @can('edit crm meetings')
                    <x-mary-menu-item wire:click="edit" title="{{ ucfirst(__('laravel-crm::lang.edit')) }}" />
                @endcan
            </x-mary-dropdown>
        @endcan
    </div>

    @can('delete crm meetings')
        @include('laravel-crm::livewire.partials.activity-delete-confirm', ['dialogId' => 'modalDeleteMeetingItem'.$meeting->id, 'what' => 'meeting'])
    @endcan
</div>
