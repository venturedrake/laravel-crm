<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.edit_product')) }}" class="mb-5" progress-indicator >

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_products')) }}" link="{{ url(route('laravel-crm.products.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-5">
            @include('laravel-crm::livewire.products.product-form')
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.products.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
