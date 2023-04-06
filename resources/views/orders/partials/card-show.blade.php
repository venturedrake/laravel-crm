@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $order->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $order,
                    'route' => 'orders'
                ]) |
                @can('edit crm orders')
                    @if($order->invoices()->count() < 1) 
                        <a href="{{ route('laravel-crm.invoices.create',['model' => 'order', 'id' => $order->id])}}" class="btn btn-success btn-sm">{{ ucwords(__('laravel-crm::lang.create_invoice')) }}</a>
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
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.orders.download', $order) }}">{{ ucfirst(__('laravel-crm::lang.download')) }}</a>
                @endcan    
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm orders')
                <a href="{{ url(route('laravel-crm.orders.edit', $order)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm orders')
                <form action="{{ route('laravel-crm.orders.destroy', $order) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.order') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row card-show card-fa-w30">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-4 text-right">Reference</dt>
                    <dd class="col-sm-8">{{ $order->reference }}</dd>
                    <dt class="col-sm-4 text-right">Description</dt>
                    <dd class="col-sm-8">{{ $order->description }}</dd>
                    @foreach($addresses as $address)
                        <dt class="col-sm-4 text-right">{{ ($address->addressType) ? ucfirst($address->addressType->name).' ' : null }}{{ ucfirst(__('laravel-crm::lang.address')) }}</dt>
                        <dd class="col-sm-8">
                            {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) }} {{ ($address->primary) ? '(Primary)' : null }}
                        </dd>
                    @endforeach
                    <dt class="col-sm-4 text-right">Labels</dt>
                    <dd class="col-sm-8">@include('laravel-crm::partials.labels',[
                            'labels' => $order->labels
                    ])</dd>
                    <dt class="col-sm-4 text-right">Owner</dt>
                    <dd class="col-sm-8">{{ $order->ownerUser->name ?? null }}</dd>
                </dl>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.client')) }}</h6>
                <hr />
                <p><span class="fa fa-address-card" aria-hidden="true"></span> @if($order->client)<a href="{{ route('laravel-crm.clients.show',$order->client) }}">{{ $order->client->name }}</a>@endif </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> @if($order->organisation)<a href="{{ route('laravel-crm.organisations.show',$order->organisation) }}">{{ $order->organisation->name }}</a>@endif</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> @if($order->person)<a href="{{ route('laravel-crm.people.show',$order->person) }}">{{ $order->person->name }}</a>@endif </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.order_items')) }} ({{ $order->orderProducts->count() }})</span></h6>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($order->orderProducts()->whereNotNull('product_id')->get() as $orderProduct)
                        <tr>
                            <td>{{ $orderProduct->product->name }}</td>
                            <td>{{ money($orderProduct->price ?? null, $orderProduct->currency) }}</td>
                            <td>{{ $orderProduct->quantity }}</td>
                            <td>{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</td>
                        </tr>
                        @if($orderProduct->comments)
                            <tr>
                                <td colspan="4" class="border-0 pt-0">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                    {{ $orderProduct->comments }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                        <td>{{ money($order->subtotal, $order->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.discount')) }}</strong></td>
                        <td>{{ money($order->discount, $order->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.tax')) }}</strong></td>
                        <td>{{ money($order->tax, $order->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.adjustment')) }}</strong></td>
                        <td>{{ money($order->adjustments, $order->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                        <td>{{ money($order->total, $order->currency) }}</td>
                    </tr>
                    </tfoot>
                </table>
                @endcan
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $order
                ])
            </div>
        </div>

    @endcomponent

@endcomponent
