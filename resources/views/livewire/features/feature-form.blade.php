<x-mary-card separator>
    <div wire:key="feature" class="space-y-3">
        <x-mary-input wire:model="title" label="{{ ucfirst(__('laravel-crm::lang.title')) }}" required />
        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
        <x-mary-select wire:model="feature_status_id" label="{{ ucfirst(__('laravel-crm::lang.status')) }}"
                       :options="$statuses"
                       option-value="id"
                       option-label="name"
                       placeholder="{{ ucfirst(__('laravel-crm::lang.select')) }}" />
        <x-mary-toggle wire:model="is_public" label="Publicly visible to portal users" />
    </div>
</x-mary-card>
