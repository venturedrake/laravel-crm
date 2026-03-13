<div class="text-left">
    <x-mary-button @click="$wire.showPayInvoice = true" class="btn-sm btn-success text-white"  label="{{ ucfirst(__('laravel-crm::lang.pay')) }}" />

    <x-mary-drawer
            wire:model="showPayInvoice"
            title="{{ ucfirst(__('laravel-crm::lang.pay_invoice')) }}"
            separator
            with-close-button
            close-on-escape
            class="w-11/12 lg:w-1/4"
            right
    >
        <x-mary-form wire:submit="pay">
            <div class="space-y-3"> 
                <x-mary-input wire:model="amount" label="{{ ucfirst(__('laravel-crm::lang.amount')) }}" prefix="$" x-mask:dynamic="$money($input)" x-on:keyup="$el.dispatchEvent(new Event('input'))" />
            </div>
    
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showPayInvoice = false" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.pay')) }}" class="btn-primary" type="submit" spinner="pay" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-drawer>
</div>
