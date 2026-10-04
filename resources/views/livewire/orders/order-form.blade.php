<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="details">
            <div
                class="autocomplete-input relative z-50"
                x-data="{ open: false }"
                @click.outside="open = false"
            >
                @if(isset($quote))
                    <x-mary-input wire:model.live="person_name" wire:keyup="searchPeople" label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}" icon="fas.user" readonly />
                @else
                    <x-mary-input
                        wire:model.live="person_name"
                        wire:keyup="searchPeople"
                        @focus="open = true"
                        @input="open = true"
                        @keydown.tab="open = false"
                        @keydown.escape="if (open) { open = false; $event.stopPropagation() }"
                        autocomplete="off"
                        label="{{ ucfirst(__('laravel-crm::lang.contact_person')) }}"
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
                                    wire:key="person-option-{{ $person->id }}"
                                    wire:click="linkPerson({{ $person->id }})"
                                    @click="open = false"
                                    :item="$person"
                                    class="cursor-pointer"
                                >
                                    <x-slot:value>
                                        {{ $person->name }}
                                    </x-slot:value>
                                </x-mary-list-item>
                            @endforeach
                        </div>
                    @endif
                    @if(! $person_id && $person_name)
                        <x-mary-badge value="New" class="badge-info badge-sm rounded-md autocomplete-new text-white" />
                    @endif
                @endif
            </div>
            <div
                class="autocomplete-input relative z-40"
                x-data="{ open: false }"
                @click.outside="open = false"
            >
                @if(isset($quote))
                    <x-mary-input wire:model.live="organization_name" wire:keyup="searchOrganizations" label="{{ ucfirst(__('laravel-crm::lang.organization')) }}" icon="fas.building" readonly />
                @else    
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
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.template')) }}" wire:model="pdf_template" :options="$this->pdfTemplateOptions()" :placeholder="$this->pdfTemplateDefaultLabel()" placeholder-value="" />
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
    <livewire:crm-model-products :model="$fromModel ?? $order ?? null" :from="$fromModel ? class_basename($fromModel) : null" wire:key="model-products-{{ $order->id ?? 'new' }}" />
</div>
