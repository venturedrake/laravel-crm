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
                <th scope="col">{{ ucwords(__('laravel-crm::lang.order')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.reference')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.customer')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="240"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($deliveries as $delivery)
                <tr>
                    <td>{{ $delivery->created_at->diffForHumans() }}</td>
                    <td><a href="{{ route('laravel-crm.orders.show', $delivery->order) }}">{{ $delivery->order->id }}</a> </td>
                    <td><a href="{{ route('laravel-crm.orders.show', $delivery->order) }}">{{ $delivery->order->reference }}</a> </td>

                    <td>
                        {{ $delivery->order->organisation->name ?? null }}<br />
                        <small>{{ $delivery->order->person->name ?? null }}</small>
                    </td>
                    <td>{{ $delivery->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                       {{--@can('edit crm deliveries')
                            @if($order->invoices()->count() < 1)
                                <a href="{{ route('laravel-crm.invoices.create',['model' => 'order', 'id' => $order->id]) }}" class="btn btn-success btn-sm">{{ ucwords(__('laravel-crm::lang.invoice')) }}</a>
                            @else
                                <a href="{{ route('laravel-crm.invoices.show',$order->invoices()->first()) }}" class="btn btn-outline-secondary btn-sm">{{ ucwords(__('laravel-crm::lang.invoiced')) }}</a>
                            @endif
                            @if($order->deliveries()->count() < 1)
                                <a href="{{ route('laravel-crm.orders.create-delivery',$order) }}" class="btn btn-success btn-sm">{{ ucwords(__('laravel-crm::lang.create_delivery')) }}</a>
                            @else
                                <a href="{{ route('laravel-crm.deliveries.show',$order->deliveries()->first()) }}" class="btn btn-outline-secondary btn-sm">{{ ucwords(__('laravel-crm::lang.delivered')) }}</a>
                            @endif
                        @endcan--}}
                        @can('view crm orders')
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.deliveries.download', $delivery) }}"><span class="fa fa-download" aria-hidden="true"></span></a>
                            {{--<a href="{{ route('laravel-crm.orders.show',$order) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>--}}
                        @endcan
                        {{--@can('edit crm orders')
                            <a href="{{ route('laravel-crm.orders.edit',$order) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan--}}
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
