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

   
    <x-mary-form x-data="crmProductForm" x-on:submit.self.prevent="submit" x-bind:aria-busy="busy">
        <fieldset x-bind:disabled="busy" x-bind:inert="busy" class="min-w-0">
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                @include('laravel-crm::livewire.orders.order-form')
            </div>
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" x-bind:inert="busy" class="btn" link="{{ url(route('laravel-crm.orders.index')) }}" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" x-bind:disabled="busy" spinner="saveWithProducts" />
            </x-slot:actions>
        </fieldset>
        <div x-show="busy" x-cloak role="status" class="mt-3"><span class="loading loading-spinner loading-sm"></span> {{ ucfirst(__('laravel-crm::lang.saving')) }}</div>
        <div x-show="failed" x-cloak role="alert" class="text-error mt-3">{{ ucfirst(__('laravel-crm::lang.product_save_failed')) }}</div>
    </x-mary-form>
</div>
