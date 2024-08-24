@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $purchaseOrder->title }}
            @if($purchaseOrder->sent == 1)
                <small><span class="badge badge-success">{{ ucfirst(__('laravel-crm::lang.sent')) }}</span></small>
            @endif
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $purchaseOrder,
                    'route' => 'purchase-orders',
                    'text' => 'back_to_purchase_orders'
                ]) |
                @livewire('send-purchase-order',[
                    'purchaseOrder' => $purchaseOrder
                ])
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.purchase-orders.download', $purchaseOrder) }}">{{ ucfirst(__('laravel-crm::lang.download')) }}</a>
                @include('laravel-crm::partials.navs.activities') |
                @if(! $purchaseOrder->xeroPurchaseOrder)
                @can('edit crm purchase orders')
                <a href="{{ url(route('laravel-crm.purchase-orders.edit', $purchaseOrder)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm purchase orders')
                <form action="{{ route('laravel-crm.purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.purchase_order') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
                @endif
                @if($purchaseOrder->xeroPurchaseOrder)
                    <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
                @endif   
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row card-show card-fa-w30">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">Number</dt>
                    <dd class="col-sm-9">{{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id }}</dd>
                    <dt class="col-sm-3 text-right">Reference</dt>
                    <dd class="col-sm-9">{{ $purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference }}</dd>
                    @hasordersenabled
                        @if($purchaseOrder->order)
                            <dt class="col-sm-3 text-right">Order</dt>
                            <dd class="col-sm-9">
                                <a href="{{ route('laravel-crm.orders.show', $purchaseOrder->order) }}">{{ $purchaseOrder->order->order_id }}</a>
                            </dd>
                        @endif
                    @endhasordersenabled
                    <dt class="col-sm-3 text-right">Issue Date</dt>
                    <dd class="col-sm-9">{{ ($purchaseOrder->issue_date) ? $purchaseOrder->issue_date->format($dateFormat) : null }}</dd>
                    <dt class="col-sm-3 text-right">Delivery Date</dt>
                    <dd class="col-sm-9">{{ ($purchaseOrder->delivery_date) ? $purchaseOrder->delivery_date->format($dateFormat) : null }}</dd>
                    <dt class="col-sm-3 text-right">Terms</dt>
                    <dd class="col-sm-9">{{ $purchaseOrder->terms }}</dd>
                    @if($purchaseOrder->delivery_type == 'pickup')
                        <dt class="col-sm-3 text-right">Delivery Type</dt>
                        <dd class="col-sm-9">Pickup</dd>
                    @else
                        <dt class="col-sm-3 text-right">Delivery Type</dt>
                        <dd class="col-sm-9">Deliver</dd>
                        <dt class="col-sm-3 text-right">Delivery Contact</dt>
                        <dd class="col-sm-9">{{ $deliveryAddress->contact ?? null }}</dd>
                        <dt class="col-sm-3 text-right">Delivery Phone</dt>
                        <dd class="col-sm-9">{{ $deliveryAddress->phone ?? null }}</dd>
                        <dt class="col-sm-3 text-right">Delivery Address</dt>
                        <dd class="col-sm-9">{{$deliveryAddress->address ?? null }}</dd>
                        <dt class="col-sm-3 text-right">Delivery Instructions</dt>
                        <dd class="col-sm-9">{{ $purchaseOrder->delivery_instructions }}</dd>
                    @endif    
                </dl>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> @if($purchaseOrder->organisation)<a href="{{ route('laravel-crm.organisations.show',$purchaseOrder->organisation) }}">{{ $purchaseOrder->organisation->name }}</a>@endif</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> @if($purchaseOrder->person)<a href="{{ route('laravel-crm.people.show',$purchaseOrder->person) }}">{{ $purchaseOrder->person->name }}</a>@endif </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.purchase_order_lines')) }} ({{ $purchaseOrder->purchaseOrderLines->count() }})</span></h6>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.price')) }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.quantity')) }}</th>
                        <th scope="col">{{ $taxName }}</th>
                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.amount')) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($purchaseOrder->purchaseOrderLines()->whereNotNull('product_id')->get() as $purchaseOrderLine)
                        <tr>
                            <td>
                                {{ $purchaseOrderLine->product->name }}
                                @if($purchaseOrderLine->product->code)
                                    <br /><small>{{ $purchaseOrderLine->product->code }}</small>
                                @endif
                            </td>
                            <td>{{ money($purchaseOrderLine->price ?? null, $purchaseOrderLine->currency) }}</td>
                            <td>{{ $purchaseOrderLine->quantity }}</td>
                            <td>{{ money($purchaseOrderLine->tax_amount ?? null, $purchaseOrderLine->currency) }}</td>
                            <td>{{ money($purchaseOrderLine->amount ?? null, $purchaseOrderLine->currency) }}</td>
                        </tr>
                        @if($purchaseOrderLine->comments)
                            <tr>
                                <td colspan="5" class="border-0 pt-0">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                    {{ $purchaseOrderLine->comments }}
                                </td>
                            </tr>
                        @endif    
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</strong></td>
                        <td>{{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><strong>{{ $taxName }}</strong></td>
                        <td>{{ money($purchaseOrder->tax, $purchaseOrder->currency) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><strong>{{ ucfirst(__('laravel-crm::lang.total')) }}</strong></td>
                        <td>{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</td>
                    </tr>
                    </tfoot>
                </table>
                @endcan
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $purchaseOrder
                ])
            </div>
        </div>

    @endcomponent

@endcomponent
