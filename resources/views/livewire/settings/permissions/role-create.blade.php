<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_role')) }}" class="mb-5" progress-indicator >
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_roles')) }}" link="{{ url(route('laravel-crm.roles.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-5">
            @include('laravel-crm::livewire.settings.permissions.role-form')
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.roles.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
