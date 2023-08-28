@component('laravel-crm::components.card')
    
    @component('laravel-crm::components.card-table')
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.created')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.reference')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.shipping_address')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.delivery_expected')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.delivered_on')) }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($deliveries as $delivery)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.deliveries.show', $delivery)) }}">
                    <td>{{ $delivery->created_at->diffForHumans() }}</td>
                    <td>{{ $delivery->delivery_id }}</td>
                    <td>
                        @if($delivery->order)
                            {{ $delivery->order->reference }}
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
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent
@endcomponent
