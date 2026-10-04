<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="details">
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
            </div>
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
            <x-mary-input wire:model="title" label="{{ ucfirst(__('laravel-crm::lang.title')) }}" />
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-input wire:model="reference" label="{{ ucfirst(__('laravel-crm::lang.reference')) }}" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
            </div>
            <div class="grid lg:grid-cols-2 gap-5 items-start">
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
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.template')) }}" wire:model="pdf_template" :options="$this->pdfTemplateOptions()" :placeholder="$this->pdfTemplateDefaultLabel()" placeholder-value="" />
            <x-crm-custom-fields :model="$quote ?? new \VentureDrake\LaravelCrm\Models\Quote()" />
        </div>
    </x-mary-card>
    <x-crm-custom-fields :model="$quote ?? new \VentureDrake\LaravelCrm\Models\Quote()" :group="true" />
</div>
<div>
   <livewire:crm-model-products :model="$quote ?? null" wire:key="model-products-{{ $quote->id ?? 'new' }}" />
</div>
