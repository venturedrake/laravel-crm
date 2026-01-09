<x-mary-card separator>
    <div wire:key="label" class="space-y-3">
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />

        <div x-data="{ color: @entangle('hex') }"
             x-init="
                picker = new Picker($refs.button);
                
                if (color) {
                  picker.setColor('#' + color, true); // `true` avoids firing change events
                }
                
                picker.onDone = rawColor => {
                    color = rawColor.hex.slice(1, 7); // Set Alpine color
                    $dispatch('hex', color); // Notify Livewire
                }
             " class="w-50">
            
            <x-mary-input x-model="color" wire:model="hex" label="{{ ucfirst(__('laravel-crm::lang.color')) }}">
                <x-slot:append>
                    <button x-ref="button"
                            class="btn join-item btn-square"
                            :style="'background-color: #' + color">
                    </button>
                </x-slot:append>
            </x-mary-input>
        </div>
        
        <x-mary-textarea wire:model="description" label="{{ ucfirst(__('laravel-crm::lang.description')) }}" rows="5" />
    </div>
</x-mary-card>