<div>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" separator>
        <div class="grid gap-3" wire:key="details">
            <div class="grid lg:grid-cols-2 gap-5">
                <x-mary-datetime wire:model="delivery_expected" label="{{ ucfirst(__('laravel-crm::lang.delivery_expected')) }}" />
                <x-mary-datetime wire:model="delivered_on" label="{{ ucfirst(__('laravel-crm::lang.delivered_on')) }}" />
            </div>
        </div>
    </x-mary-card>
    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.shipping_address')) }}" class="mt-5" separator>
        <div class="grid gap-3" wire:key="addresses">
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
        </div>
    </x-mary-card>
</div>
<div>
    <livewire:crm-model-products :model="$fromModel ?? $delivery ?? null" creating="Delivery" :from="$fromModel ? class_basename($fromModel) : null" />
</div>
