<div class="grid lg:grid-cols-12 gap-3">
    <div class="col-span-6">
        <x-mary-input wire:model="addresses.{{ $addressType }}.contact" label="{{ ucfirst(__('laravel-crm::lang.contact_name')) }}" />
    </div>
    <div class="col-span-6">
        <x-mary-input wire:model="addresses.{{ $addressType }}.phone" label="{{ ucfirst(__('laravel-crm::lang.contact_phone')) }}" />
    </div>
</div>
<x-mary-input wire:model="addresses.{{ $addressType }}.line1" label="{{ ucfirst(__('laravel-crm::lang.line_1')) }}" />
<x-mary-input wire:model="addresses.{{ $addressType }}.line2" label="{{ ucfirst(__('laravel-crm::lang.line_2')) }}" />
<x-mary-input wire:model="addresses.{{ $addressType }}.line3" label="{{ ucfirst(__('laravel-crm::lang.line_3')) }}" />
<div class="grid lg:grid-cols-12 gap-3">
    <div class="col-span-6">
        <x-mary-input wire:model="addresses.{{ $addressType }}.city" label="{{ ucfirst(__('laravel-crm::lang.suburb')) }}" />
    </div>
    <div class="col-span-6">
        <x-mary-input wire:model="addresses.{{ $addressType }}.state" label="{{ ucfirst(__('laravel-crm::lang.state')) }}" />
    </div>
</div>
<div class="grid lg:grid-cols-12 gap-3">
    <div class="col-span-6">
        <x-mary-input wire:model="addresses.{{ $addressType }}.code" label="{{ ucfirst(__('laravel-crm::lang.postcode')) }}" />
    </div>
    <div class="col-span-6">
        <x-mary-select wire:model="addresses.{{ $addressType }}.country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="$countries" required />
    </div>
</div>
