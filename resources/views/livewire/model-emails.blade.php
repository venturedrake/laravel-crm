<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.emails')) }}" class="mb-5" separator>
    <x-slot:menu>
        <x-mary-button wire:click="add" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <div class="grid gap-3" wire:key="emails">
        @foreach($data as $index => $email)
            <div class="grid lg:grid-cols-12 gap-3">
                <div class="col-span-5">
                    <x-mary-input wire:model.blur="data.{{ $index }}.address" label="{{ ucfirst(__('laravel-crm::lang.email')) }}" />
                </div>
                <div class="col-span-4">
                    <x-mary-select wire:model="data.{{ $index }}.type" :options="[]" label="{{ ucfirst(__('laravel-crm::lang.type')) }}"/>
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
        @endforeach
    </div>
</x-mary-card>