@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.deliveries')) }}
        @endslot

        @slot('actions')
           {{-- @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.deliveries.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Delivery'
            ])--}}
           {{-- @can('create crm deliveries')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.deliveries.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_delivery')) }}</a></span>
            @endcan--}}
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.created')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.number')) }}</th>
                @hasordersenabled
                <th scope="col">{{ ucwords(__('laravel-crm::lang.reference')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.order')) }}</th>
                @endhasordersenabled
                <th scope="col">{{ ucwords(__('laravel-crm::lang.customer')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.shipping_address')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.delivery_expected')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.delivered_on')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="240"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($deliveries as $delivery)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.deliveries.show', $delivery)) }}">
                    <td>{{ $delivery->created_at->diffForHumans() }}</td>
                    <td>{{ $delivery->delivery_id }}</td>
                    @hasordersenabled
                    <td>
                        @if($delivery->order)
                            {{ $delivery->order->reference }}
                        @endif
                    </td>
                    <td>
                        @if($delivery->order)
                            <a href="{{ route('laravel-crm.orders.show', $delivery->order) }}">{{ $delivery->order->order_id }}</a>
                        @endif
                    </td>
                    @endhasordersenabled
                    <td>
                        @if($delivery->order)
                            @if($delivery->order->client)
                                {{ $delivery->order->client->name }}
                            @endif
                            @if($delivery->order->organisation)
                                @if($delivery->order->client)<br /><small>@endif
                                    {{ $delivery->order->organisation->name }}
                                    @if($delivery->order->client)</small>@endif
                            @endif
                            @if($delivery->order->organisation && $delivery->order->person)
                                <br /><small>{{ $delivery->order->person->name }}</small>
                            @elseif($delivery->order->person)
                                {{ $delivery->order->person->name }}
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($address = $delivery->getShippingAddress())
                            {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) }} {{ ($address->primary) ? '(Primary)' : null }}
                            @if($address->contact)
                                <small><br >{{ ucwords(__('laravel-crm::lang.contact')) }}: {{ $address->contact }}</small>
                            @endif
                            @if($address->phone)
                                <small><br >{{ ucwords(__('laravel-crm::lang.phone')) }}: {{ $address->phone }}</small>
                            @endif
                        @endif    
                    </td>
                    <td>
                        {{ $delivery->delivery_expected ?? null }}
                    </td>
                    <td>
                        {{ $delivery->delivered_on ?? null }}
                    </td>
                    <td>{{ $delivery->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('view crm deliveries')
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.deliveries.download', $delivery) }}"><span class="fa fa-download" aria-hidden="true"></span></a>
                            <a href="{{ route('laravel-crm.deliveries.show',$delivery) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm deliveries')
                            <a href="{{ route('laravel-crm.deliveries.edit',$delivery) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm deliveries')
                            <form action="{{ route('laravel-crm.deliveries.destroy',$delivery) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.delivery') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent

    @if($deliveries instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $deliveries->links() }}
        @endcomponent
    @endif

@endcomponent
