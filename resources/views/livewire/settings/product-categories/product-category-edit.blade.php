<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.edit_product_category')) }}" class="mb-5" progress-indicator >
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_product_categories')) }}" link="{{ url(route('laravel-crm.product-categories.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-form wire:submit="save">
        @include('laravel-crm::livewire.settings.product-categories.product-category-form')
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.product-categories.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
