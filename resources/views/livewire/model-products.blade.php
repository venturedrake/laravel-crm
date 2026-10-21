{{-- A deal only seeds the lines of a new quote, so it needs no update right of its own. --}}
@php($canManageProducts = ! $model || $model instanceof \VentureDrake\LaravelCrm\Models\Deal || (auth()->user()?->can('update', $model) ?? false))
<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.products')) }}" separator>
    @if($canManageProducts && ! $from && class_basename($model) != 'Delivery')
        <x-slot:menu>
            <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
        </x-slot:menu>
    @endif
    <div class="grid gap-3" wire:key="data-products">
        <div class="overflow-x-auto">
            <table class="table">
                <tbody class="model-products-rows">
                @foreach($products as $index => $product)
                    <tr wire:key="product-row-{{ $index }}" class="hover:bg-base-300 cursor-grab">
                        <td class="px-3 relative" colspan="2">
                            <div class="space-y-3">
                                @if(in_array($from, ['Quote', 'Order']) || class_basename($model) == 'Delivery')
                                    <x-mary-input wire:model="products.{{ $index }}.name" readonly />
                                @else
                                    <x-mary-select wire:model.live="products.{{ $index }}.id"
                                                   :options="\VentureDrake\LaravelCrm\Models\Product::orderBy('name')->get() ?? []"
                                                   placeholder="{{ ucfirst(__('laravel-crm::lang.select_product')) }}"
                                                   label="{{ ucfirst(__('laravel-crm::lang.name')) }}" single>
                                        @if($dynamicProducts)
                                            <x-slot:append>
                                                <x-mary-button @click="$wire.showCreateProduct = true" {{--label="{{ ucfirst(__('laravel-crm::lang.create')) }}"--}} icon="o-plus" class="join-item btn-primary" />
                                            </x-slot:append>
                                        @endif
                                    </x-mary-select>

                                    {{-- Only a product with variations gets the select - or a line
                                         already on one, which keeps it listed even once it has been
                                         deactivated or deleted. The placeholder posts '' (no variation). --}}
                                    @php($variationOptions = $this->variationOptions($product['id'] ?? null, $product['product_variation_id'] ?? null))
                                    @if(count($variationOptions) > 0)
                                        <x-mary-select wire:model.live="products.{{ $index }}.product_variation_id"
                                                       :options="$variationOptions"
                                                       placeholder="{{ ucfirst(__('laravel-crm::lang.no_variation')) }}"
                                                       placeholder-value=""
                                                       label="{{ ucfirst(__('laravel-crm::lang.variation')) }}" />
                                    @endif

                                    <div class="absolute top-3 right-3">
                                        <x-mary-icon name="fas.arrows-alt-v"/>
                                        @if($canManageProducts)
                                            <x-mary-button wire:click="remove({{ $index }})" class="btn-xs btn-error btn-square text-white" type="button" icon="fas.x" />
                                        @endif
                                    </div>
                                @endif
                                <div class="grid {{ ($creating != 'Delivery') ? 'lg:grid-cols-4' : null }} gap-2">
                                    @if($creating != 'Delivery')
                                        @if(in_array($from, ['Quote', 'Order']) && $creating != 'PurchaseOrder')
                                            <x-mary-input wire:model.live.debounce.500ms="products.{{ $index }}.unit_price" label="{{ ucfirst(__('laravel-crm::lang.price')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" readonly />
                                        @else
                                            <x-mary-input wire:model.live.debounce.500ms="products.{{ $index }}.unit_price" label="{{ ucfirst(__('laravel-crm::lang.price')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" />
                                        @endif
                                    @endif

                                    {{-- The cap is bound rather than wrapped in an @if inside the tag:
                                         component tags compile before directives, so an @if between the
                                         attributes stops the tag matching and it renders as literal text.
                                         A null :max is dropped from the attribute bag and a null :hint
                                         reads as "not passed". --}}
                                    @php($quantityMax = $product['quantity_max'] ?? null)
                                    @php($quantityMaxLabel = $quantityMax !== null ? \VentureDrake\LaravelCrm\Support\Quantity::format($quantityMax) : null)
                                    <x-mary-input wire:model.live.debounce.500ms="products.{{ $index }}.quantity"
                                                  label="{{ ucfirst(__('laravel-crm::lang.quantity')) }}"
                                                  type="number" step="0.001" min="0" inputmode="decimal"
                                                  :max="$quantityMaxLabel"
                                                  :hint="$quantityMaxLabel !== null ? ucfirst(__('laravel-crm::lang.remaining')).': '.$quantityMaxLabel : null" />

                                    @if($creating != 'Delivery')    
                                        <x-mary-input wire:model.blur="products.{{ $index }}.tax_amount" label="{{ ucfirst(__('laravel-crm::lang.tax')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" readonly />
                                        <x-mary-input wire:model.blur="products.{{ $index }}.amount" label="{{ ucfirst(__('laravel-crm::lang.amount')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" readonly />
                                    @endif    
                                </div>

                                @if($creating != 'Delivery')
                                    @if(in_array($from, ['Quote', 'Order']))
                                        <x-mary-input wire:model.blur="products.{{ $index }}.comments" label="{{ ucfirst(__('laravel-crm::lang.comments')) }}" readonly />
                                    @else
                                        <x-mary-input wire:model.blur="products.{{ $index }}.comments" label="{{ ucfirst(__('laravel-crm::lang.comments')) }}" />
                                    @endif
                                @endif    
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                @if($creating != 'Delivery')
                    <tfoot id="quoteProductsTotals">
                    @if($canManageProducts && ! $from && class_basename($model) != 'Delivery')
                        <tr>
                            <td class="text-right px-0 py-3 border-y border-base-content/10" colspan="2">
                                <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-right pb-1 pt-3 pr-5">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</td>
                        <td class="text-right pb-1 pt-3 px-0">
                            <x-mary-input wire:model="sub_total" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" placeholder="0.00" readonly />
                        </td>
                    </tr>
                    <tr>
                        <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.discount')) }}</td>
                        <td class="text-right py-1 px-0">
                            <x-mary-input wire:model="discount" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" placeholder="0.00" readonly />
                        </td>
                    </tr>
                    <tr>
                        <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.tax')) }}</td>
                        <td class="text-right py-1 px-0">
                            <x-mary-input wire:model="tax" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" placeholder="0.00" readonly />
                        </td>
                    </tr>
                    <tr>
                        <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</td>
                        <td class="text-right py-1 px-0">
                            <x-mary-input wire:model="adjustment" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" placeholder="0.00" readonly />
                        </td>
                    </tr>
                    <tr>
                        <td class="text-right py-1 pr-5">{{ ucfirst(__('laravel-crm::lang.total')) }}</td>
                        <td class="text-right py-1 px-0">
                            <x-mary-input wire:model="total" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" placeholder="0.00" readonly />
                        </td>
                    </tr>
                    </tfoot>
                @endif    
            </table>
        </div>

        <x-mary-drawer
                wire:model="showCreateProduct"
                title="{{ ucfirst(__('laravel-crm::lang.create_product')) }}"
                separator
                with-close-button
                close-on-escape
                class="w-11/12 lg:w-1/2"
                right
        >
            <livewire:crm-product-create wire:model="showCreateProduct" layout="drawer" wire:key="crm-product-create" />
        </x-mary-drawer>
    </div>
</x-mary-card>
