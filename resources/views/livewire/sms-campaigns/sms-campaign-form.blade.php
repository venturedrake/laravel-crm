<div class="grid lg:grid-cols-2 gap-5">
    <div>
        <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" />
    </div>
    <div>
        <x-mary-select wire:model.live="sms_template_id"
                       :options="$templates"
                       option-label="name"
                       option-value="id"
                       label="{{ ucfirst(__('laravel-crm::lang.sms_template')) }}"
                       placeholder="-" />
    </div>
    <div class="lg:col-span-2">
        <x-mary-input wire:model="from" label="{{ ucfirst(__('laravel-crm::lang.sender_id')) }}" hint="{{ __('laravel-crm::lang.sender_id_hint') }}" />
    </div>
    <div class="lg:col-span-2 grid lg:grid-cols-6 gap-5">
        <div class="lg:col-span-5">
            <x-mary-textarea wire:model.live.debounce.300ms="body" rows="6" label="{{ ucfirst(__('laravel-crm::lang.body')) }}" />
            <div class="text-xs text-base-content/60 mt-1">
                {{ mb_strlen($body ?? '') }} {{ __('laravel-crm::lang.characters') }} —
                {{ $this->segmentCount }} {{ __('laravel-crm::lang.sms_segments') }}
            </div>
        </div>
        <div class="lg:col-span-1">
            @include('laravel-crm::livewire.sms-campaigns._placeholders', ['placeholders' => $placeholders])
        </div>
    </div>
</div>
