<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="details">
            <div class="autocomplete-input z-50">
                <x-mary-input wire:model.live="person_name" wire:keyup="searchPeople" wire:blur="hidePeople" label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" icon="fas.user" />
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
            <div class="autocomplete-input z-40">
                <x-mary-input wire:model.live="organization_name" wire:keyup="searchOrganizations" wire:blur="hideOrganizations" label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" />
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
                @if(! $organization_id && $organization_name)
                    <x-mary-badge value="New" class="badge-info badge-sm rounded-md autocomplete-new text-white" />
                @endif
            </div>
            <x-mary-input wire:model="title" label="{{ ucfirst(__('laravel-crm::lang.title')) }}" />
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-input wire:model="reference" label="{{ ucfirst(__('laravel-crm::lang.reference')) }}" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
            </div>
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-datetime wire:model="issue_at" label="{{ ucfirst(__('laravel-crm::lang.issue_date')) }}" />
                <x-mary-datetime wire:model="expire_at" label="{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}" />
            </div>
            <x-mary-textarea wire:model="terms" label="{{ ucfirst(__('laravel-crm::lang.terms')) }}" rows="5" />
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
    <livewire:crm-model-products :model="$quote ?? null" />
</div>
