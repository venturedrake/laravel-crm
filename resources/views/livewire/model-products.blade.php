<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.products')) }}" separator>
    <x-slot:menu>
        <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <div class="grid gap-3" wire:key="data-products">
        <div class="overflow-x-auto">
            <table class="table">
                <tbody id="sortableItems">
                @foreach($data as $index => $product)
                    <tr class="hover:bg-base-300 cursor-grab">
                        <td class="px-0 relative" colspan="2">
                            <div class="space-y-3">
                                <x-mary-select wire:model="data.{{ $index }}.id"
                                               :options="\VentureDrake\LaravelCrm\Models\Product::orderBy('name')->get() ?? []"
                                               label="{{ ucfirst(__('laravel-crm::lang.name')) }}" single>
                                    <x-slot:append>
                                        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create')) }}" icon="o-plus" class="join-item btn-primary" />
                                    </x-slot:append>
                                </x-mary-select>
                                <div class="absolute top-3 right-0">
                                    <x-mary-icon name="fas.arrows-alt-v"/>
                                    <x-mary-button wire:click="remove({{ $index }})" class="btn-xs btn-error btn-square text-white" type="button" icon="fas.x" />
                                </div>
                                <div class="grid lg:grid-cols-4 gap-2">
                                    <x-mary-input wire:model.blur="data.{{ $index }}.unit_price" label="{{ ucfirst(__('laravel-crm::lang.price')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" />
                                    <x-mary-input wire:model.blur="data.{{ $index }}.quantity" label="{{ ucfirst(__('laravel-crm::lang.quantity')) }}" type="number" />
                                    <x-mary-input wire:model.blur="data.{{ $index }}.tax_amount" label="{{ ucfirst(__('laravel-crm::lang.tax')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" readonly />
                                    <x-mary-input wire:model.blur="data.{{ $index }}.amount" label="{{ ucfirst(__('laravel-crm::lang.amount')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" readonly />
                                </div>
                                <x-mary-input wire:model.blur="data.{{ $index }}.comments" label="{{ ucfirst(__('laravel-crm::lang.comments')) }}" />
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot id="quoteProductsTotals">
                <tr>
                    <td class="text-right px-0 py-3 border-y border-base-content/10" colspan="2">
                        <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
                    </td>
                </tr>
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
            </table>
        </div>
    </div>
</x-mary-card>
