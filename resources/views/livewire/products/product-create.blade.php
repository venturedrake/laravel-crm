<div class="crm-content">
    @if($layout == 'full')
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_product')) }}" class="mb-5" progress-indicator >
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_products')) }}" link="{{ url(route('laravel-crm.products.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    @endif

    @if($layout == 'full')
        <x-mary-form wire:submit="save">
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                @include('laravel-crm::livewire.products.product-form', [
                     'layout' => $layout
                 ])
            </div>
            @if($layout == 'full')
                <x-slot:actions>
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.products.index')) }}" />
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
                </x-slot:actions>
            @else
                <x-slot:actions>
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showCreateProduct = false" />
                    <x-mary-button wire:click="createProduct" label="{{ ucfirst(__('laravel-crm::lang.create')) }}" class="btn-primary" type="button" spinner="createProduct" />
                </x-slot:actions>
            @endif
        </x-mary-form>
    @else
        <x-mary-form wire:submit="createProduct">
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                @include('laravel-crm::livewire.products.product-form', [
                     'layout' => $layout
                 ])
            </div>
            @if($layout == 'full')
                <x-slot:actions>
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.products.index')) }}" />
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
                </x-slot:actions>
            @else
                <x-slot:actions>
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" @click="$wire.showCreateProduct = false" />
                    <x-mary-button wire:click="createProduct" label="{{ ucfirst(__('laravel-crm::lang.create')) }}" class="btn-primary" type="button"    />
                </x-slot:actions>
            @endif
        </x-mary-form>
    @endif
</div>
