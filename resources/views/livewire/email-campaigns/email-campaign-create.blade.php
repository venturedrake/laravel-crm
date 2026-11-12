<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create')) }} {{ __('laravel-crm::lang.email_campaign') }}" />

    <x-mary-form wire:submit="save">
        <x-mary-card shadow>
            @include('laravel-crm::livewire.email-campaigns.email-campaign-form')
        </x-mary-card>

        @include('laravel-crm::livewire.email-campaigns._audience-send')

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="{{ route('laravel-crm.email-campaigns.index') }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.preview')) }}" wire:click="openPreview" spinner="openPreview" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>

    @include('laravel-crm::livewire.email-campaigns._preview-drawer')
    @include('laravel-crm::livewire.email-campaigns._confirm-send')
</div>
