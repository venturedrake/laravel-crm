<div class="crm-content">
    <x-mary-header title="{{ $widget?->id ? ucfirst(__('laravel-crm::lang.edit_chat_widget')) : ucfirst(__('laravel-crm::lang.create_chat_widget')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="{{ url(route('laravel-crm.chat-widgets.index')) }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <form wire:submit="save" class="grid gap-4">
            <x-mary-input label="Name" wire:model="name" />
            <x-mary-input label="Welcome message" wire:model="welcome_message" />
            <x-mary-input label="Color" wire:model="color" type="color" />
            <x-mary-select label="Position" wire:model="position" :options="[
                ['id' => 'bottom-right', 'name' => 'Bottom right'],
                ['id' => 'bottom-left', 'name' => 'Bottom left'],
            ]" />
            <x-mary-toggle label="Active" wire:model="is_active" />
            <div>
                <x-mary-button type="submit" label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" spinner="save" />
            </div>
        </form>
    </x-mary-card>
</div>

