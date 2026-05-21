<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.submit_feature')) }}" class="mb-5" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }} {{ __('laravel-crm::lang.to') }} {{ __('laravel-crm::lang.features') }}" link="{{ url(route('laravel-crm.features.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-form wire:submit="save">
        @include('laravel-crm::livewire.features.feature-form')
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.features.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
