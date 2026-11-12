{{-- "Position" on a person, "Positions" on an organisation. --}}
<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.'.($this->forPerson() ? 'position' : 'positions'))) }}" shadow separator>
    <x-slot:menu>
        <x-mary-button
            wire:click="$toggle('showPast')"
            class="btn-sm {{ $showPast ? 'btn-primary text-white' : 'btn-outline' }}"
            type="button"
            icon="fas.clock-rotate-left"
            tooltip-left="{{ ucfirst(__('laravel-crm::lang.'.($showPast ? 'hide_past_positions' : 'show_past_positions'))) }}"
        />
        @can('create', \VentureDrake\LaravelCrm\Models\Position::class)
            <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" spinner="add" />
        @endcan
    </x-slot:menu>

    <x-mary-drawer
        wire:model="showPositionForm"
        title="{{ ucfirst(__('laravel-crm::lang.'.($positionId ? 'edit_position' : 'add_position'))) }}"
        separator
        with-close-button
        close-on-escape
        class="w-11/12 lg:w-1/3"
        right
    >
        <x-mary-form wire:submit="save">
            <div class="space-y-3">
                @if($positionId)
                    <x-mary-input value="{{ $person_name }}" label="{{ ucfirst(__('laravel-crm::lang.person')) }}" icon="fas.user" readonly disabled />
                @elseif(! $this->forPerson())
                    <div
                        class="autocomplete-input relative z-50"
                        x-data="{ open: false }"
                        @click.outside="open = false"
                    >
                        <x-mary-input
                            wire:model.live="person_name"
                            wire:keyup="searchPeople"
                            @focus="open = true"
                            @input="open = true"
                            @keydown.tab="open = false"
                            @keydown.escape="if (open) { open = false; $event.stopPropagation() }"
                            autocomplete="off"
                            label="{{ ucfirst(__('laravel-crm::lang.person')) }}"
                            icon="fas.user"
                        />
                        @if($showPeople && !empty($people))
                            <div
                                x-show="open"
                                x-cloak
                                class="border border-solid border-primary absolute bg-base-100 dark:bg-base-200 z-40 w-96"
                            >
                                @foreach($people as $person)
                                    <x-mary-list-item
                                        wire:key="position-person-option-{{ $person->id }}"
                                        wire:click="linkPerson({{ $person->id }})"
                                        @click="open = false"
                                        :item="$person"
                                        class="cursor-pointer"
                                    >
                                        <x-slot:value>
                                            {{ $person->name }}
                                        </x-slot:value>
                                        @if($person->current_position_title)
                                            <x-slot:sub-value>
                                                {{ $person->current_position_title }}
                                            </x-slot:sub-value>
                                        @endif
                                    </x-mary-list-item>
                                @endforeach
                            </div>
                        @endif
                        @if(! $person_id && $person_name)
                            <x-mary-badge value="New" class="badge-info badge-sm rounded-md autocomplete-new text-white" />
                        @endif
                    </div>
                @endif

                @if($positionId)
                    <x-mary-input value="{{ $organization_name }}" label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" readonly disabled />
                @elseif($this->forPerson())
                    <div
                        class="autocomplete-input relative z-40"
                        x-data="{ open: false }"
                        @click.outside="open = false"
                    >
                        <x-mary-input
                            wire:model.live="organization_name"
                            wire:keyup="searchOrganizations"
                            @focus="open = true"
                            @input="open = true"
                            @keydown.tab="open = false"
                            @keydown.escape="if (open) { open = false; $event.stopPropagation() }"
                            autocomplete="off"
                            label="{{ ucfirst(__('laravel-crm::lang.organization')) }}"
                            icon="fas.building"
                        />
                        @if($showOrganizations && !empty($organizations))
                            <div
                                x-show="open"
                                x-cloak
                                class="border border-solid border-primary absolute bg-base-100 dark:bg-base-200 z-50 w-96"
                            >
                                @foreach($organizations as $organization)
                                    <x-mary-list-item
                                        wire:key="position-org-option-{{ $organization->id }}"
                                        wire:click="linkOrganization({{ $organization->id }})"
                                        @click="open = false"
                                        :item="$organization"
                                        class="cursor-pointer"
                                    >
                                        <x-slot:value>
                                            {{ $organization->name }}
                                        </x-slot:value>
                                    </x-mary-list-item>
                                @endforeach
                            </div>
                        @endif
                        @if(! $organization_id && $organization_name)
                            <x-mary-badge value="New" class="badge-info badge-sm rounded-md autocomplete-new text-white" />
                        @endif
                    </div>
                @endif

                <x-mary-input
                    wire:model.live.debounce.400ms="title"
                    label="{{ ucwords(__('laravel-crm::lang.position_title')) }}"
                    icon="fas.briefcase"
                    list="position-title-suggestions-{{ $this->getId() }}"
                    autocomplete="off"
                />
                <datalist id="position-title-suggestions-{{ $this->getId() }}">
                    @foreach($titleSuggestions as $suggestion)
                        <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>

                <div class="grid lg:grid-cols-2 gap-5 items-start">
                    <x-mary-datetime wire:model="start_date" label="{{ ucfirst(__('laravel-crm::lang.start_date')) }}" />
                    <x-mary-datetime wire:model="end_date" label="{{ ucfirst(__('laravel-crm::lang.end_date')) }}" />
                </div>

                <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="3" />

                @if($conflicts->isNotEmpty())
                    <div class="rounded-md border border-primary p-3 space-y-2" wire:key="position-conflicts">
                        <div class="font-semibold">{{ ucfirst(__('laravel-crm::lang.positions_to_end')) }}</div>
                        <div class="text-sm opacity-70">{{ ucfirst(__('laravel-crm::lang.positions_to_end_hint')) }}</div>
                        @foreach($conflicts as $conflict)
                            <label class="flex items-start gap-2 cursor-pointer" wire:key="position-conflict-{{ $conflict->id }}">
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm checkbox-primary mt-0.5"
                                    wire:click="toggleKeepOpen({{ $conflict->id }})"
                                    @checked(! in_array($conflict->id, $keepOpen))
                                />
                                <span>
                                    <strong>{{ $conflict->title }}</strong>
                                    &middot; {{ $conflict->person?->name }}
                                    <span class="opacity-70">
                                        ({{ __('laravel-crm::lang.since') }} {{ $conflict->start_date?->format($dateFormat) }})
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showPositionForm = false" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-drawer>

    @if(! $this->forPerson())
        <x-mary-input
            wire:model.live.debounce.300ms="search"
            placeholder="{{ ucfirst(__('laravel-crm::lang.search_positions')) }}..."
            icon="o-magnifying-glass"
            class="input-sm"
            clearable
        />
    @endif

    <div class="grid gap-y-3 {{ $this->forPerson() ? '' : 'mt-4' }}">
        @forelse($positions as $position)
            <div class="flex flex-row justify-between items-start gap-3 {{ $position->isCurrent() ? '' : 'opacity-60' }}" wire:key="position-{{ $position->id }}">
                <div class="flex flex-row items-start gap-2">
                    <x-mary-icon name="fas.briefcase" class="mt-1" />
                    <div>
                        <div class="font-semibold">{{ $position->title }}</div>
                        <div>
                            @php($other = $this->forPerson() ? $position->organization : $position->person)
                            <x-crm-record-link :record="$other" :route="$this->forPerson() ? 'laravel-crm.organizations.show' : 'laravel-crm.people.show'" :label="$other?->name" class="link link-hover link-primary" />
                        </div>
                        <div class="text-sm opacity-70">
                            @if($position->end_date)
                                {{ $position->start_date?->format($dateFormat) }} &ndash; {{ $position->end_date->format($dateFormat) }}
                            @else
                                {{ ucfirst(__('laravel-crm::lang.since')) }} {{ $position->start_date?->format($dateFormat) }}
                            @endif
                        </div>
                        @if($position->description)
                            <div class="text-sm">{{ $position->description }}</div>
                        @endif
                    </div>
                </div>
                <div class="flex flex-row gap-1 shrink-0">
                    @can('update', $position)
                        @if($position->isCurrent())
                            <x-mary-button
                                wire:click="end({{ $position->id }})"
                                wire:confirm="{{ ucfirst(__('laravel-crm::lang.end_position_confirm')) }}"
                                class="btn-xs btn-square btn-outline"
                                type="button"
                                icon="fas.flag-checkered"
                                tooltip-left="{{ ucfirst(__('laravel-crm::lang.end_position')) }}"
                            />
                        @endif
                        <x-mary-button wire:click="edit({{ $position->id }})" class="btn-xs btn-square btn-outline" type="button" icon="o-pencil-square" />
                    @endcan
                    @can('delete', $position)
                        <x-mary-button
                            wire:click="delete({{ $position->id }})"
                            wire:confirm="{{ ucfirst(__('laravel-crm::lang.delete_position_confirm')) }}"
                            class="btn-xs btn-error btn-square text-white"
                            type="button"
                            icon="o-trash"
                        />
                    @endcan
                </div>
            </div>
        @empty
            <div class="opacity-60">
                {{ ucfirst(__('laravel-crm::lang.'.($showPast || $search !== '' ? 'no_positions' : 'no_current_positions'))) }}
            </div>
        @endforelse
    </div>
</x-mary-card>
