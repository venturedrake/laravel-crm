<div>
    <!-- CSS -->
    <style type="text/css">
        .autocomplete-input .clear{
            clear:both;
            margin-top: 20px;
        }

        .autocomplete-input ul{
            list-style: none;
            padding: 0px;
            position: absolute;
            margin: 0;
            background: white;
        }

        .autocomplete-input ul li{
            background: lavender;
            padding: 4px;
            margin-bottom: 1px;
        }

        .autocomplete-input ul li:hover{
            cursor: pointer;
        }
        
    </style>
    
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_lead')) }}" class="mb-5" progress-indicator >

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_leads')) }}" link="{{ url(route('laravel-crm.leads.index')) }}" icon="fas.angle-double-left" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

   
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-8">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
                <div class="grid gap-3 lg:px-3" wire:key="details">
                    <div class="autocomplete-input z-10">
                        <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" wire:model="organization" wire:keyup="searchOrganizations" />
                        @if($showOrganizations)
                            <div class="border border-solid border-primary absolute bg-white z-50 w-96">
                                @if(!empty($organizations))
                                    @foreach($organizations as $organization)
                                        <x-mary-list-item wire:click="linkOrganization({{ $organization->id }})" :item="$organization">
                                            <x-slot:value>
                                                {{ $organization->name }}
                                            </x-slot:value>
                                        </x-mary-list-item>
                                    @endforeach
                                @endif  
                            </div>
                        @endif
                    </div>
                    <div class="autocomplete-input z-10">
                        <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" icon="fas.user" wire:model="person" wire:keyup="searchPeople"  />
                        @if($showPeople)
                            <div class="border border-solid border-primary absolute bg-white z-50 w-96">
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
                    </div>
                    <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.title')) }}" wire:model="title" />
                    <x-mary-textarea label="{{ ucfirst(__('laravel-crm::lang.description')) }}" wire:model="description" rows="5" />
                </div>
            </x-mary-card>
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="/products" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
