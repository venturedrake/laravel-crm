<x-mary-card separator>
    <div wire:key="tax-rate" class="space-y-3">
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
        <x-mary-input wire:model="rate" label="{{ ucfirst(__('laravel-crm::lang.rate')) }}"  suffix="%" />
        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
        <x-mary-toggle wire:model="default" label="{{ ucfirst(__('laravel-crm::lang.default_tax_rate')) }}" />
        <x-mary-input wire:model="tax_type" label="{{ ucfirst(__('laravel-crm::lang.tax_type')) }}" />
    </div>
</x-mary-card>