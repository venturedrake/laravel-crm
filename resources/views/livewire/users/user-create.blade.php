<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_user')) }}" class="mb-5" progress-indicator >

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_users')) }}" link="{{ url(route('laravel-crm.users.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-5 items-start">
           @include('laravel-crm::livewire.users.user-form', ['isCreate' => true])
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.users.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
