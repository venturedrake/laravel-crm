<x-mary-card separator>
    <div wire:key="label" class="space-y-3">
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
        <x-mary-input wire:model="hex" label="{{ ucfirst(__('laravel-crm::lang.color')) }}" data-coloris />
        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
    </div>
</x-mary-card>