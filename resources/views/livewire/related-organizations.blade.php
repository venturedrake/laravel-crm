<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.related_organizations')) }}" shadow separator>
    @can('update', $model)
    <x-slot:menu>
        <x-mary-button @click="$wire.showAddRelatedOrganization = true" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <x-mary-drawer
            wire:model="showAddRelatedOrganization"
            title="{{ ucfirst(__('laravel-crm::lang.link_an_organization')) }}"
            separator
            with-close-button
            close-on-escape
            class="w-11/12 lg:w-1/3"
            right
    >
        <x-mary-form wire:submit="add">
            <div class="space-y-3">
                <div
                    class="autocomplete-input relative z-50"
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
                        label="{{ ucfirst(__('laravel-crm::lang.name')) }}"
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
                                    wire:key="org-option-{{ $organization->id }}"
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
            </div>

            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showAddRelatedOrganization = false" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.link_organization')) }}" class="btn-primary text-white" type="submit" spinner="add" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-drawer>
    @endcan
    <div class="grid gap-y-5">
        @foreach($this->contacts as $contact)
            <x-mary-list-item :item="$contact">
                <x-slot:value>
                    <div class="flex flex-row justify-between">
                        <div class="flex flex-row items-center gap-2">
                            <x-mary-icon name="fas.building" />
                            <a href="{{ route('laravel-crm.organizations.show', $contact->entityable) }}" class="link link-hover link-primary">
                                {{ $contact->entityable->name }}
                            </a>
                        </div>
                        <div>
                            @can('update', $model)
                                <x-mary-button wire:click="remove({{ $contact->entityable->id }})" class="btn-xs btn-error btn-square text-white" type="button" icon="fas.x" />
                            @endcan
                        </div>
                    </div>

                </x-slot:value>
            </x-mary-list-item>
        @endforeach
    </div>
</x-mary-card>
