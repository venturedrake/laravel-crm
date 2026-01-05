<x-mary-card separator>
    <div wire:key="label" class="space-y-3">
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
    </div>
</x-mary-card>