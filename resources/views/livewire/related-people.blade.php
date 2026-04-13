<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.related_people')) }}" shadow separator>
    <x-slot:menu>
        <x-mary-button  @click="$wire.showAddRelatedPerson = true" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <x-mary-drawer
            wire:model="showAddRelatedPerson"
            title="{{ ucfirst(__('laravel-crm::lang.link_a_person')) }}"
            separator
            with-close-button
            close-on-escape
            class="w-11/12 lg:w-1/3"
            right
    >
        <x-mary-form wire:submit="add">
            <div class="space-y-3">
                <div class="autocomplete-input z-50">
                    <x-mary-input wire:model.live="person_name" wire:keyup="searchPeople" wire:blur="hidePeople" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" icon="fas.user" />
                    @if($showPeople)
                        <div class="border border-solid border-primary absolute bg-white z-40 w-96">
                            @if(!empty($people))
                                @foreach($people as $person)
                                    <x-mary-list-item wire:click="linkPerson({{ $person->id }})" :item="$person">
                                        <x-slot:value>
                                            {{ $person->name }}
                                        </x-slot:value>
                                    </x-mary-list-item>
                                @endforeach
                            @endif
                        </div>
                    @endif
                    @if(! $person_id && $person_name)
                        <x-mary-badge value="New" class="badge-info badge-sm rounded-md autocomplete-new text-white" />
                    @endif
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showAddRelatedPerson = false" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.link_person')) }}" class="btn-primary text-white" type="submit" spinner="add" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-drawer>
    <div class="grid gap-y-5">
        @foreach($this->contacts as $contact)
            <x-mary-list-item wire:click="showContact({{ $contact->id }})" :item="$contact">
                <x-slot:value>
                    {{ $contact->contactable->name }}
                </x-slot:value>
            </x-mary-list-item>
        @endforeach
    </div>
</x-mary-card>