<div class="crm-content">
    <x-mary-header title="Edit Feature Status" class="mb-5" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Back to Feature Statuses" link="{{ url(route('laravel-crm.feature-statuses.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-form wire:submit="save">
        @include('laravel-crm::livewire.settings.feature-statuses.feature-status-form')
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.feature-statuses.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save_changes')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
