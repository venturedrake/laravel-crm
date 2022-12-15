<form method="POST" action="{{ url(route('laravel-crm.orders.store')) }}">
    @csrf
    @component('laravel-crm::components.card')

        @component('laravel-crm::components.card-header')

            @slot('title')
                {{ ucfirst(__('laravel-crm::lang.create_order')) }}
            @endslot

            @slot('actions')
                @include('laravel-crm::partials.return-button',[
                    'model' => new \VentureDrake\LaravelCrm\Models\Order(),
                    'route' => 'orders'
                ])
            @endslot

        @endcomponent

        @component('laravel-crm::components.card-body')

            @include('laravel-crm::orders.partials.fields')

        @endcomponent

        @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.orders.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        @endcomponent

    @endcomponent
</form>
