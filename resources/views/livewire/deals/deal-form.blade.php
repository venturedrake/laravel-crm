<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" separator>
        <div class="grid gap-3" wire:key="person">
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
            <x-mary-input wire:model="phone" label="{{ ucfirst(__('laravel-crm::lang.phone')) }}">
                <x-slot:append>
                    <x-mary-select wire:model="phone_type" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes(false)" class="join-item bg-base-200 w-80"/>
                </x-slot:append>
            </x-mary-input>
            <x-mary-input wire:model="email" label="{{ ucfirst(__('laravel-crm::lang.email')) }}">
                <x-slot:append>
                    <x-mary-select wire:model="email_type" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes(false)" class="join-item bg-base-200" />
                </x-slot:append>
            </x-mary-input>
        </div>
    </x-mary-card>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.organization')) }}" separator>
        <div class="grid gap-3" wire:key="organization">
            <div class="autocomplete-input z-40">
                <x-mary-input wire:model.live="organization_name" wire:keyup="searchOrganizations" wire:blur="hideOrganizations" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" icon="fas.building" />
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
</div>
<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="details">
            <x-mary-input wire:model="title" label="{{ ucfirst(__('laravel-crm::lang.title')) }}" />
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-input label="{{ ucfirst(__('laravel-crm::lang.value')) }}" wire:model="amount" prefix="$" money />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
            </div>
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.stage')) }}" wire:model="pipeline_stage_id" :options="$pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get() ?? []" />
                <x-mary-datetime wire:model="expected_close" label="{{ ucfirst(__('laravel-crm::lang.expected_close_date')) }}" />
            </div>
            <x-mary-choices-offline
                    wire:model="labels"
                    label="{{ ucfirst(__('laravel-crm::lang.labels')) }}"
                    :options="\VentureDrake\LaravelCrm\Models\Label::get()"
                    placeholder="Search ..."
                    searchable />
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
        </div>
    </x-mary-card>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.products')) }}" separator>
        <x-slot:menu>
            <x-mary-button wire:click="addProduct" class="btn-sm btn-square" type="button" icon="fas.plus" />
        </x-slot:menu>
        <div id="dealProducts" class="grid gap-3" wire:key="products">
            @foreach($products as $index => $product)
                <div class="grid lg:grid-cols-10 gap-3 items-end">
                    <input type="hidden" wire:model="products.{{ $index }}.deal_product_id" />
                    <span class="lg:col-span-4">
                                    <x-mary-select wire:model="products.{{ $index }}.id" :options="\VentureDrake\LaravelCrm\Models\Product::orderBy('name')->get() ?? []" label="{{ ($loop->first) ? ucfirst(__('laravel-crm::lang.item')) : null }}" />
                                </span>
                    <span class="lg:col-span-2">
                                    <x-mary-input wire:model.blur="products.{{ $index }}.price" label="{{ ($loop->first) ? ucfirst(__('laravel-crm::lang.price'))  : null}}" prefix="$" money />
                                </span>
                    <span class="lg:col-span-2">
                                    <x-mary-input wire:model.blur="products.{{ $index }}.quantity" label="{{ ($loop->first) ? ucfirst(__('laravel-crm::lang.quantity')) : null }}" type="number" />
                                </span>
                    <span class="lg:col-span-2">
                                    <x-mary-input wire:model="products.{{ $index }}.amount" label="{{ ($loop->first) ? ucfirst(__('laravel-crm::lang.amount')) : null }}" prefix="$" money readonly />
                                </span>
                </div>
            @endforeach
        </div>
    </x-mary-card>
</div>
