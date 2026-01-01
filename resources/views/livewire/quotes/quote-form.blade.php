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
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.quote_products')) }}" separator>
        <x-slot:menu>
            <x-mary-button wire:click="addProduct" class="btn-sm btn-square" type="button" icon="fas.plus" />
        </x-slot:menu>
        <div class="grid gap-3" wire:key="products">
            <div class="overflow-x-auto">
                <table class="table">
                    <tbody id="sortableItems">
                    @foreach($products as $index => $product)
                        <tr class="hover:bg-base-300 cursor-grab">
                            <td class="px-0 relative" colspan="2">
                                <div class="space-y-3">
                                    <x-mary-select wire:model="products.{{ $index }}.id"
                                                   :options="\VentureDrake\LaravelCrm\Models\Product::orderBy('name')->get() ?? []"
                                                   label="{{ ucfirst(__('laravel-crm::lang.name')) }}"/>
                                    <div class="absolute top-3 right-0">
                                        <x-mary-icon name="fas.arrows-alt-v"/>
                                        <x-mary-button wire:click="removeProduct({{ $index }})" class="btn-xs btn-error btn-square text-white" type="button" icon="fas.x" />
                                    </div>
                                    <div class="grid lg:grid-cols-4 gap-2">
                                        <x-mary-input wire:model.blur="products.{{ $index }}.unit_price" label="{{ ucfirst(__('laravel-crm::lang.price')) }}" prefix="$" money />
                                        <x-mary-input wire:model.blur="products.{{ $index }}.quantity" label="{{ ucfirst(__('laravel-crm::lang.quantity')) }}" type="number" />
                                        <x-mary-input wire:model.blur="products.{{ $index }}.tax_amount" label="{{ ucfirst(__('laravel-crm::lang.tax')) }}" prefix="$" money readonly />
                                        <x-mary-input wire:model.blur="products.{{ $index }}.amount" label="{{ ucfirst(__('laravel-crm::lang.amount')) }}" prefix="$" money readonly />
                                    </div>
                                    <x-mary-input wire:model.blur="products.{{ $index }}.comments" label="{{ ucfirst(__('laravel-crm::lang.comments')) }}" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot id="quoteProductsTotals">
                        <tr>
                            <td class="text-right px-0 py-3 border-y border-base-content/10" colspan="2"> 
                                <x-mary-button wire:click="addProduct" class="btn-sm btn-square" type="button" icon="fas.plus" />
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right pb-1 pt-3 pr-5">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                            <td class="text-right pb-1 pt-3 px-0">
                                <x-mary-input wire:model="sub_total" prefix="$" money readonly />
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                            <td class="text-right py-1 px-0">
                                <x-mary-input wire:model="sub_total" prefix="$" money readonly />
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                            <td class="text-right py-1 px-0">
                                <x-mary-input wire:model="sub_total" prefix="$" money readonly />
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</td>
                            <td class="text-right py-1 px-0">
                                <x-mary-input wire:model="sub_total" prefix="$" money readonly />
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                            <td class="text-right py-1 px-0">
                                <x-mary-input wire:model="sub_total" prefix="$" money readonly />
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </x-mary-card>
</div>
