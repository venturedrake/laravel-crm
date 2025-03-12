<div>
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_lead')) }}" class="mb-5" progress-indicator >

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_leads')) }}" link="{{ url(route('laravel-crm.leads.index')) }}" icon="fas.angle-double-left" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

   
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-8">
            <div>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>

                    <div class="grid gap-3 lg:px-3" wire:key="details">
                        <div class="autocomplete-input z-50">
                            <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" wire:model="organization" wire:keyup="searchOrganizations" wire:blur="hideOrganizations" />
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
                            @if(! $organization_id && $organization)
                                <x-mary-badge value="New" class="badge-primary autocomplete-new text-white" />
                            @endif
                        </div>
                        <div class="autocomplete-input z-40">
                            <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" icon="fas.user" wire:model="person" wire:keyup="searchPeople" wire:blur="hidePeople" />
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
                            @if(! $person_id && $person)
                                <x-mary-badge value="New" class="badge-primary autocomplete-new text-white" />
                            @endif
                        </div>
                        <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.title')) }}" wire:model="title" />
                        <x-mary-textarea label="{{ ucfirst(__('laravel-crm::lang.description')) }}" wire:model="description" rows="5" />
                        <div class="grid lg:grid-cols-2 gap-5">
                            <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.value')) }}" wire:model="value" prefix="$" />
                            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
                        </div>
                        <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.stage')) }}" wire:model="pipeline_stage_id" :options="$pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get() ?? []" />
                        <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.labels')) }}" wire:model="labels" />
                        <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
                    </div>
                </x-mary-card>
            </div>
            <div>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.organization')) }}" class="mb-8" separator>
                    <div class="grid gap-3 lg:px-3" wire:key="organization">

                    </div>
                </x-mary-card>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" separator>
                    <div class="grid gap-3 lg:px-3" wire:key="person">

                    </div>
                </x-mary-card>
                
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="/products" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
