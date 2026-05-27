<x-mary-card separator>
    <div wire:key="feature-status" class="space-y-3">
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="3" />
        <x-mary-input wire:model="color" label="Color" placeholder="#6c757d" />
        <x-mary-input wire:model="order" type="number" label="Order" />
        <x-mary-toggle wire:model="is_default" label="Default status for new features" />
        <x-mary-toggle wire:model="is_closed" label="Closed (declined / completed)" />
    </div>
</x-mary-card>
