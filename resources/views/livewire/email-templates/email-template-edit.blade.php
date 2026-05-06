<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.edit')) }} {{ __('laravel-crm::lang.email_template') }} — {{ $template->name }}" />

    <x-mary-form wire:submit="save">
        <x-mary-card shadow>
            @include('laravel-crm::livewire.email-templates.email-template-form')
        </x-mary-card>

        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" link="{{ route('laravel-crm.email-templates.show', $template) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.preview')) }}" wire:click="openPreview" spinner="openPreview" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>

    @include('laravel-crm::livewire.email-campaigns._preview-drawer')
</div>
