<div>
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.edit_lead')) }}" class="mb-5" progress-indicator >

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_leads')) }}" link="{{ url(route('laravel-crm.leads.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
        </x-slot:actions>
    </x-mary-header>

   
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-8">
            <div>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>

                    <div class="grid gap-3 lg:px-3" wire:key="details">
                        <div class="autocomplete-input z-40">
                            <x-mary-input wire:model.live="person" wire:keyup="searchPeople" wire:blur="hidePeople" label="{{ ucfirst(__('laravel-crm::lang.contact')) }}" icon="fas.user" />
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
                        <div class="autocomplete-input z-50">
                            <x-mary-input wire:model.live="organization" wire:keyup="searchOrganizations" wire:blur="hideOrganizations" label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" />
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
                        <x-mary-input wire:model="title" label="{{ ucfirst(__('laravel-crm::lang.title')) }}" />
                        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
                        <div class="grid lg:grid-cols-2 gap-5">
                            <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.value')) }}" wire:model="amount" prefix="$" />
                            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
                        </div>
                        <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.stage')) }}" wire:model="pipeline_stage_id" :options="$pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get() ?? []" />
                        <x-mary-choices-offline
                                wire:model="labels"
                                label="{{ ucfirst(__('laravel-crm::lang.labels')) }}"
                                :options="\VentureDrake\LaravelCrm\Models\Label::get()"
                                placeholder="Search ..."
                                searchable />
                        <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
                    </div>
                </x-mary-card>
            </div>
            <div>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.organization')) }}" class="mb-8" separator>
                    <div class="grid gap-3 lg:px-3" wire:key="organization">
                        <x-mary-input wire:model="address_line_1" label="{{ ucfirst(__('laravel-crm::lang.address_line_1')) }}" />
                        <x-mary-input wire:model="address_line_2" label="{{ ucfirst(__('laravel-crm::lang.address_line_2')) }}" />
                        <x-mary-input wire:model="address_line_3" label="{{ ucfirst(__('laravel-crm::lang.address_line_3')) }}" />
                        <div class="grid lg:grid-cols-2 gap-5">
                            <x-mary-input wire:model="address_suburb" label="{{ ucfirst(__('laravel-crm::lang.suburb')) }}" />
                            <x-mary-input wire:model="address_state" label="{{ ucfirst(__('laravel-crm::lang.state')) }}" />
                        </div>
                        <div class="grid lg:grid-cols-2 gap-5">
                            <x-mary-input wire:model="address_postcode" label="{{ ucfirst(__('laravel-crm::lang.postcode')) }}" />
                            <x-mary-select wire:model="address_country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries()" />
                        </div>
                    </div>
                </x-mary-card>
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" separator>
                    <div class="grid gap-3 lg:px-3" wire:key="person">
                        <x-mary-input wire:model="phone" label="{{ ucfirst(__('laravel-crm::lang.phone')) }}">
                            <x-slot:append>
                                <x-mary-select wire:model="phone_type" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes(false)" class="rounded-s-none bg-base-200" />
                            </x-slot:append>
                        </x-mary-input>
                        <x-mary-input wire:model="email" label="{{ ucfirst(__('laravel-crm::lang.email')) }}">
                            <x-slot:append>
                                <x-mary-select wire:model="email_type" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes(false)" class="rounded-s-none bg-base-200" />
                            </x-slot:append>
                        </x-mary-input>
                    </div>
                </x-mary-card>
                
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.leads.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
