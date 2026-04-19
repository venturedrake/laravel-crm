<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_quote')) }}" class="mb-5" progress-indicator >
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_quotes')) }}" link="{{ url(route('laravel-crm.quotes.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid lg:grid-cols-2 gap-5 items-start">
        @include('laravel-crm::livewire.quotes.quote-form')
    </div>
    <hr class="border-t-[length:var(--border)] border-base-content/10 my-3">
    <div class="flex justify-end gap-3">
        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.quotes.index')) }}" />
        <x-mary-button wire:click="save" label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
    </div>
</div>
