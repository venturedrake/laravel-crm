<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_invoice')) }}" class="mb-5" progress-indicator >
        <x-slot:title>
            {{ ucfirst(__('laravel-crm::lang.create_invoice')) }} @isset($order){{ __('laravel-crm::lang.from_order') }} <a href="{{ route('laravel-crm.orders.show', $order) }}" class="link link-hover link-primary">{{ $order->order_id }}</a> @endisset
        </x-slot:title>
        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_invoices')) }}" link="{{ url(route('laravel-crm.invoices.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>

   
    <x-mary-form x-data="crmProductForm" x-on:submit.self.prevent="submit" x-bind:aria-busy="busy">
        <fieldset x-bind:disabled="busy" x-bind:inert="busy" class="min-w-0">
            <div class="grid lg:grid-cols-2 gap-5 items-start">
                @include('laravel-crm::livewire.invoices.invoice-form')
            </div>
            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" x-bind:inert="busy" class="btn" link="{{ url(route('laravel-crm.invoices.index')) }}" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" x-bind:disabled="busy" spinner="saveWithProducts" />
            </x-slot:actions>
        </fieldset>
        <div x-show="busy" x-cloak role="status" class="mt-3"><span class="loading loading-spinner loading-sm"></span> {{ ucfirst(__('laravel-crm::lang.saving')) }}</div>
        <div x-show="failed" x-cloak role="alert" class="text-error mt-3">{{ ucfirst(__('laravel-crm::lang.product_save_failed')) }}</div>
    </x-mary-form>
</div>
