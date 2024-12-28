<form method="POST" action="{{ url(route('laravel-crm.invoices.store')) }}">
    @csrf
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                {{ ucfirst(__('laravel-crm::lang.create_invoice')) }} @isset($order){{ __('laravel-crm::lang.from_order') }} <a href="{{ route('laravel-crm.orders.show', $order) }}">{{ $order->order_id }}</a> @endisset
            @endslot

            @slot('actions')
                @include('laravel-crm::partials.return-button',[
                    'model' => new \VentureDrake\LaravelCrm\Models\Invoice(),
                    'route' => 'invoices'
                ])
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::invoices.partials.fields')

        @endcomponent

        @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.invoices.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        @endcomponent

    @endcomponent
</form>
