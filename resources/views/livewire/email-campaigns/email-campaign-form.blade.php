<div class="grid lg:grid-cols-2 gap-5">
    <div>
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
    </div>
    <div>
        <x-mary-select wire:model.live="email_template_id"
                       :options="$templates"
                       option-label="name"
                       option-value="id"
                       label="{{ ucfirst(__('laravel-crm::lang.email_template')) }}"
                       placeholder="-" />
    </div>
    <div class="lg:col-span-2">
        <x-mary-input wire:model="subject" label="{{ ucfirst(__('laravel-crm::lang.subject')) }}" />
    </div>
    <div class="lg:col-span-2">
        <x-mary-input wire:model="preview_text" label="{{ ucfirst(__('laravel-crm::lang.preview_text')) }}" hint="{{ __('laravel-crm::lang.preview_text_hint') }}" />
    </div>
    <div class="lg:col-span-2 grid lg:grid-cols-6 gap-5">
        <div class="lg:col-span-5">
            <x-mary-editor wire:model="body" gpl-license :config="['min_height' => 600, 'autoresize_bottom_margin' => 20, 'plugins' => 'autoresize']" label="{{ ucfirst(__('laravel-crm::lang.body')) }}" />
        </div>
        <div class="lg:col-span-1">
            @include('laravel-crm::livewire.email-campaigns._placeholders', ['placeholders' => $placeholders])
        </div>
    </div>
</div>
