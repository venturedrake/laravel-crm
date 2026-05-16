<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="details">
            <div class="autocomplete-input z-50">
                @if(isset($quote))
                    <x-mary-input wire:model.live="person_name" wire:keyup="searchPeople" wire:blur="hidePeople" label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" icon="fas.user" readonly />
                @else
                    <x-mary-input wire:model.live="person_name" wire:keyup="searchPeople" wire:blur="hidePeople" label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" icon="fas.user" />
                    @if($showPeople)
                        <div class="border border-solid border-primary absolute bg-base-100 dark:bg-base-200 z-40 w-96">
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
                @endif
            </div>
            <div class="autocomplete-input z-40">
                @if(isset($quote))
                    <x-mary-input wire:model.live="organization_name" wire:keyup="searchOrganizations" wire:blur="hideOrganizations" label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" readonly />
                @else    
                    <x-mary-input wire:model.live="organization_name" wire:keyup="searchOrganizations" wire:blur="hideOrganizations" label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" />
                    @if($showOrganizations)
                        <div class="border border-solid border-primary absolute bg-base-100 dark:bg-base-200 z-50 w-96">
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
                @endif    
            </div>
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-input wire:model="reference" label="{{ ucfirst(__('laravel-crm::lang.reference')) }}" />
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
            <x-crm-custom-fields :model="$order ?? new \VentureDrake\LaravelCrm\Models\Order()" />
        </div>
    </x-mary-card>
    <x-crm-custom-fields :model="$order ?? new \VentureDrake\LaravelCrm\Models\Order()" :group="true" />
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.addresses')) }}" class="mt-5" separator>
        <div class="grid gap-3" wire:key="addresses">
            <x-mary-tabs wire:model="selectedAddressTab">
                <x-mary-tab name="billing" label="Billing">
                    <div class="grid lg:grid-cols-12 gap-3">
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.billing.contact" label="{{ ucfirst(__('laravel-crm::lang.contact_name')) }}" />
                        </div>
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.billing.phone" label="{{ ucfirst(__('laravel-crm::lang.contact_phone')) }}" />
                        </div>
                    </div>
                    <x-mary-input wire:model="addresses.billing.line1" label="{{ ucfirst(__('laravel-crm::lang.line_1')) }}" />
                    <x-mary-input wire:model="addresses.billing.line2" label="{{ ucfirst(__('laravel-crm::lang.line_2')) }}" />
                    <x-mary-input wire:model="addresses.billing.line3" label="{{ ucfirst(__('laravel-crm::lang.line_3')) }}" />
                    <div class="grid lg:grid-cols-12 gap-3">
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.billing.city" label="{{ ucfirst(__('laravel-crm::lang.suburb')) }}" />
                        </div>
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.billing.state" label="{{ ucfirst(__('laravel-crm::lang.state')) }}" />
                        </div>
                    </div>
                    <div class="grid lg:grid-cols-12 gap-3">
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.billing.code" label="{{ ucfirst(__('laravel-crm::lang.postcode')) }}" />
                        </div>
                        <div class="col-span-6">
                            <x-mary-select wire:model="addresses.billing.country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="$countries" required />
                        </div>
                    </div>
                </x-mary-tab>
                <x-mary-tab name="shipping" label="Shipping">
                    <div class="grid lg:grid-cols-12 gap-3">
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.shipping.contact" label="{{ ucfirst(__('laravel-crm::lang.contact_name')) }}" />
                        </div>
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.shipping.phone" label="{{ ucfirst(__('laravel-crm::lang.contact_phone')) }}" />
                        </div>
                    </div>
                    <x-mary-input wire:model="addresses.shipping.line1" label="{{ ucfirst(__('laravel-crm::lang.line_1')) }}" />
                    <x-mary-input wire:model="addresses.shipping.line2" label="{{ ucfirst(__('laravel-crm::lang.line_2')) }}" />
                    <x-mary-input wire:model="addresses.shipping.line3" label="{{ ucfirst(__('laravel-crm::lang.line_3')) }}" />
                    <div class="grid lg:grid-cols-12 gap-3">
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.shipping.city" label="{{ ucfirst(__('laravel-crm::lang.suburb')) }}" />
                        </div>
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.shipping.state" label="{{ ucfirst(__('laravel-crm::lang.state')) }}" />
                        </div>
                    </div>
                    <div class="grid lg:grid-cols-12 gap-3">
                        <div class="col-span-6">
                            <x-mary-input wire:model="addresses.shipping.code" label="{{ ucfirst(__('laravel-crm::lang.postcode')) }}" />
                        </div>
                        <div class="col-span-6">
                            <x-mary-select wire:model="addresses.shipping.country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="$countries" required />
                        </div>
                    </div>
                </x-mary-tab>
            </x-mary-tabs>
        </div>
    </x-mary-card>
</div>
<div>
    <livewire:crm-model-products :model="$fromModel ?? $order ?? null" :from="$fromModel ? class_basename($fromModel) : null" />
</div>
