<div>
    <div class="card bg-base-100 {{ ($layout == 'full') ? 'p-5' : null }}">
        <div class="grid gap-3" wire:key="product">
            <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-input wire:model="code" label="{{ strtoupper(__('laravel-crm::lang.sku')) . ' (Stock Keeping Unit)' }}" />
                <x-mary-input wire:model="barcode" label="{{ ucwords(__('laravel-crm::lang.barcode')) . ' (ISBN, UPC, GTIN, etc)', }}" />
            </div>
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.category')) }}" wire:model="product_category" :options="$productCategories" />
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-input wire:model="purchase_account" label="{{ ucfirst(__('laravel-crm::lang.purchase_account')) }}" />
                <x-mary-input wire:model="sales_account" label="{{ ucfirst(__('laravel-crm::lang.sales_account')) }}" />
            </div>
            <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
        </div>
    </div>
</div>
<div>
    <div class="card bg-base-100 {{ ($layout == 'full') ? 'p-5' : null }}">
        <div class="grid gap-3" wire:key="product">
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-input wire:model="unit" label="{{ ucfirst(__('laravel-crm::lang.unit')) }}" />
                <x-mary-input wire:model="unit_price" label="{{ ucfirst(__('laravel-crm::lang.unit_price')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" />
            </div>
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-select wire:model.live="tax_rate_id" label="{{ ucfirst(__('laravel-crm::lang.tax_rate')) }}" :options="$taxRates" />
                <x-mary-input wire:model="tax_rate" label="{{ ucfirst(__('laravel-crm::lang.tax_rate_percent')) }}" suffix="%" readonly />
            </div>
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.currency')) }}" wire:model="currency" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencyOptions()" />
                <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.owner')) }}" wire:model="user_owner_id" :options="\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false)" />
            </div>
            <x-crm-custom-fields :model="$product ?? new \VentureDrake\LaravelCrm\Models\Product()" />
        </div>
    </div>
    <x-crm-custom-fields :model="$product ?? new \VentureDrake\LaravelCrm\Models\Product()" :group="true" />
</div>
<div class="lg:col-span-2" wire:key="product-variations">
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.variations')) }}" separator class="{{ ($layout == 'full') ? null : 'p-0' }}">
        <x-slot:menu>
            <x-mary-button wire:click="addVariation" type="button" icon="fas.plus" class="btn-sm btn-square" tooltip-left="{{ ucfirst(__('laravel-crm::lang.add_variation')) }}" spinner="addVariation" />
        </x-slot:menu>
        <div class="grid gap-3">
            @foreach($variations as $index => $variation)
                {{-- Only classes already in the compiled CSS (public/vendor/laravel-crm) are used here. --}}
                <div wire:key="variation-row-{{ $index }}" class="grid gap-2 pb-3 border-b border-base-200">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <x-mary-input wire:model="variations.{{ $index }}.name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
                        </div>
                        <div class="flex gap-1 pb-1">
                            @if(! $loop->first)
                                <x-mary-button wire:click="moveVariationUp({{ $index }})" type="button" icon="fas.arrow-up" class="btn-sm btn-square btn-ghost" />
                            @endif
                            @if(! $loop->last)
                                <x-mary-button wire:click="moveVariationDown({{ $index }})" type="button" icon="fas.arrow-down" class="btn-sm btn-square btn-ghost" />
                            @endif
                            <x-mary-button wire:click="removeVariation({{ $index }})" type="button" icon="fas.x" class="btn-sm btn-square btn-error text-white" />
                        </div>
                    </div>
                    <div class="grid lg:grid-cols-4 gap-2 items-end">
                        <x-mary-input wire:model="variations.{{ $index }}.code" label="{{ strtoupper(__('laravel-crm::lang.sku')) }}" />
                        <x-mary-input wire:model="variations.{{ $index }}.barcode" label="{{ ucfirst(__('laravel-crm::lang.barcode')) }}" />
                        <x-mary-input wire:model="variations.{{ $index }}.unit_price" label="{{ ucfirst(__('laravel-crm::lang.price')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" />
                        <div class="pb-3">
                            <x-mary-toggle wire:model="variations.{{ $index }}.active" label="{{ ucfirst(__('laravel-crm::lang.active')) }}" />
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-base-content/60">{{ ucfirst(__('laravel-crm::lang.variation_price_fallback')) }}</p>
                <x-mary-button wire:click="addVariation" type="button" icon="fas.plus" label="{{ ucfirst(__('laravel-crm::lang.add_variation')) }}" class="btn-sm btn-outline" spinner="addVariation" />
            </div>
        </div>
    </x-mary-card>
</div>