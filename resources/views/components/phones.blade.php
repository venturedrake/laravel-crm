<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.phone_numbers')) }}" class="mb-5" separator>
    <x-slot:menu>
        <x-mary-button wire:click="addPhone" class="btn-sm btn-square" type="button" icon="fas.plus" />
    </x-slot:menu>
    <div class="grid gap-3" wire:key="phones">
        @foreach($phones as $index => $phone)
            <div class="grid lg:grid-cols-12 gap-3">
                <div class="col-span-5">
                    <x-mary-input wire:model.blur="phones.{{ $index }}.number" label="{{ ucfirst(__('laravel-crm::lang.phone')) }}" />
                </div>
                <div class="col-span-4">
                    <x-mary-select wire:model="phones.{{ $index }}.type" :options="$phoneTypes" label="{{ ucfirst(__('laravel-crm::lang.type')) }}"/>
                </div>
                <div class="col-span-2">
                    <fieldset class="fieldset py-0">
                        <legend class="fieldset-legend mb-0.5">{{ ucfirst(__('laravel-crm::lang.primary')) }}</legend>
                        <div class="pt-1 text-center">
                            <x-mary-toggle wire:model="phones.{{ $index }}.primary"  />
                        </div>
                    </fieldset>
                </div>
                <div class="col-span-1 text-center">
                    <fieldset class="fieldset py-0">
                        <legend class="fieldset-legend mb-0.5">&nbsp;</legend>
                        <div class="pt-1 text-center">
                            <x-mary-button wire:click="deletePhone({{ $index }})" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        </div>
                    </fieldset>

                </div>
            </div>
        @endforeach
    </div>
</x-mary-card>