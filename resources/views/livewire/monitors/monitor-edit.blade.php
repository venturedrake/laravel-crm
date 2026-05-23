<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.edit')) }} {{ __('laravel-crm::lang.monitor') }} — {{ $monitor->monitor_id }}" />

    <x-mary-form wire:submit="save">
        <x-mary-card shadow>
            @include('laravel-crm::livewire.monitors._monitor-form')
        </x-mary-card>

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="{{ route('laravel-crm.monitors.show', $monitor) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
