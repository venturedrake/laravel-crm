<form method="POST" action="{{ url(route('laravel-crm.purchase-orders.update', $purchaseOrder)) }}">
    @csrf
    @method('PUT')
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                {{ ucfirst(__('laravel-crm::lang.edit_purchase_order')) }}
            @endslot

            @slot('actions')
                @include('laravel-crm::partials.return-button',[
                    'model' => $purchaseOrder,
                    'route' => 'purchase-orders',
                    'text' => 'back_to_purchase_orders'
                ])
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::purchase-orders.partials.fields')

        @endcomponent

        @component('laravel-crm::components.card-footer')
            <a href="{{ url(route('laravel-crm.purchase-orders.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
            <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
        @endcomponent

    @endcomponent
</form>
