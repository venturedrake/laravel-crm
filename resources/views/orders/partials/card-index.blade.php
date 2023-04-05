@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.orders')) }}
        @endslot

        @slot('actions')
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.orders.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Order'
            ])
            @can('create crm orders')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.orders.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_order')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.created')) }}</th>
                <th scope="col">#</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.customer')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.sub_total')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.discount')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.tax')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.adjustment')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.total')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="240"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($orders as $order)
               <tr class="has-link" data-url="{{ url(route('laravel-crm.orders.show', $order)) }}">
                   <td>{{ $order->created_at->diffForHumans() }}</td>
                   <td>{{ $order->reference }}</td>
                   <td>@include('laravel-crm::partials.labels',[
                            'labels' => $order->labels,
                            'limit' => 3
                        ])</td>
                    <td>
                        @if($order->client)
                            {{ $order->client->name }}
                        @endif
                        @if($order->organisation)
                            @if($order->client)<br /><small>@endif
                                {{ $order->organisation->name }}
                                @if($order->client)</small>@endif
                        @endif
                        @if($order->organisation && $order->person)
                            <br /><small>{{ $order->person->name }}</small>
                        @elseif($order->person)
                            {{ $order->person->name }}
                        @endif
                    </td>
                    <td>{{ money($order->subtotal, $order->currency) }}</td>
                    <td>{{ money($order->discount, $order->currency) }}</td>
                    <td>{{ money($order->tax, $order->currency) }}</td>
                    <td>{{ money($order->adjustments, $order->currency) }}</td>
                    <td>{{ money($order->total, $order->currency) }}</td>
                    <td>{{ $order->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('edit crm orders')
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
                        @endcan
                        @can('view crm orders')
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.orders.download', $order) }}"><span class="fa fa-download" aria-hidden="true"></span></a>
                            <a href="{{ route('laravel-crm.orders.show',$order) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm orders')
                        <a href="{{ route('laravel-crm.orders.edit',$order) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm orders')
                        <form action="{{ route('laravel-crm.orders.destroy',$order) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.order') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent

    @if($orders instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $orders->links() }}
        @endcomponent
    @endif

@endcomponent
