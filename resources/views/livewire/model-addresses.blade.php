<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.addresses')) }}" class="mb-5" separator>
    <x-slot:menu>
        <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <div class="grid gap-3" wire:key="addresses">
        @foreach($data as $index => $address)
            <div class="grid lg:grid-cols-12 gap-3">
                <div class="col-span-9">
                    <x-mary-select wire:model="data.{{ $index }}.type" :options="[]" label="{{ ucfirst(__('laravel-crm::lang.type')) }}" />
                </div>
                <div class="col-span-2">
                    <fieldset class="fieldset py-0">
                        <legend class="fieldset-legend mb-0.5">{{ ucfirst(__('laravel-crm::lang.primary')) }}</legend>
                        <div class="pt-1 text-center">
                            <x-mary-toggle wire:model="data.{{ $index }}.primary"  />
                        </div>
                    </fieldset>
                </div>
                <div class="col-span-1 text-center">
                    <fieldset class="fieldset py-0">
                        <legend class="fieldset-legend mb-0.5">&nbsp;</legend>
                        <div class="pt-1 text-center">
                            <x-mary-button wire:click="delete({{ $index }})" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="grid lg:grid-cols-12 gap-3">
                <div class="col-span-6">
                    <x-mary-input wire:model="data.{{ $index }}.contact" label="{{ ucfirst(__('laravel-crm::lang.contact_name')) }}" />
                </div>
                <div class="col-span-6">
                    <x-mary-input wire:model="data.{{ $index }}.phone" label="{{ ucfirst(__('laravel-crm::lang.contact_phone')) }}" />
                </div>
            </div>
            <x-mary-input wire:model="data.{{ $index }}.line1" label="{{ ucfirst(__('laravel-crm::lang.line_1')) }}" />
            <x-mary-input wire:model="data.{{ $index }}.line2" label="{{ ucfirst(__('laravel-crm::lang.line_2')) }}" />
            <x-mary-input wire:model="data.{{ $index }}.line3" label="{{ ucfirst(__('laravel-crm::lang.line_3')) }}" />
            <div class="grid lg:grid-cols-12 gap-3">
                <div class="col-span-6">
                    <x-mary-input wire:model="data.{{ $index }}.city" label="{{ ucfirst(__('laravel-crm::lang.suburb')) }}" />
                </div>
                <div class="col-span-6">
                    <x-mary-input wire:model="data.{{ $index }}.state" label="{{ ucfirst(__('laravel-crm::lang.state')) }}" />
                </div>
            </div>
            <div class="grid lg:grid-cols-12 gap-3">
                <div class="col-span-6">
                    <x-mary-input wire:model="data.{{ $index }}.code" label="{{ ucfirst(__('laravel-crm::lang.postcode')) }}" />
                </div>
                <div class="col-span-6">
                    <x-mary-select wire:model="data.{{ $index }}.country" label="{{ ucfirst(__('laravel-crm::lang.country')) }}" :options="$countries" required />
                </div>
            </div>
        @endforeach
    </div>
</x-mary-card>