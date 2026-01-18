<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.related_organizations')) }}" shadow separator>
    <x-slot:menu>
        <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <div class="grid gap-y-5">

    </div>
</x-mary-card>