<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.create_purchase_order')) }}" class="mb-5" progress-indicator >
        <x-slot:title>
            {{ ucfirst(__('laravel-crm::lang.create_purchase_order')) }} @isset($order){{ __('laravel-crm::lang.from_order') }} <a href="{{ route('laravel-crm.orders.show', $order) }}" class="link link-hover link-primary">{{ $order->order_id }}</a> @endisset
        </x-slot:title>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_purchase_orders')) }}" link="{{ url(route('laravel-crm.purchase-orders.index')) }}" icon="fas.angle-double-left" class="btn-sm" responsive />
        </x-slot:actions>
    </x-mary-header>

   
    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-2 gap-5 items-start">
            @include('laravel-crm::livewire.purchase-orders.purchase-order-form')
        </div>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.cancel')) }}" class="btn" link="{{ url(route('laravel-crm.purchase-orders.index')) }}" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" class="btn-primary text-white" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
