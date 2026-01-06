<x-mary-card separator>
    <div wire:key="label" class="space-y-3">
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
        <x-mary-select wire:model="pipeline_id" label="{{ ucfirst(__('laravel-crm::lang.pipeline')) }}" :options="$pipelines" required />
    </div>
</x-mary-card>