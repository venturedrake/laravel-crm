<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header class="mb-5" progress-indicator >
        <x-slot:title>
            {{ ucfirst(__('laravel-crm::lang.create_order')) }} @isset($quote){{ __('laravel-crm::lang.from_quote') }} <a href="{{ route('laravel-crm.quotes.show', $quote) }}" class="link link-hover link-primary">{{ $quote->quote_id }}</a> @endisset
        </x-slot:title>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_orders')) }}" link="{{ url(route('laravel-crm.orders.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>

   
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-5 items-start">
            @include('laravel-crm::livewire.orders.order-form')
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.orders.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
