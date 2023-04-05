@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $delivery->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $delivery,
                    'route' => 'deliveries'
                ]) | 
                @can('view crm deliveries')
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('laravel-crm.deliveries.download', $delivery) }}">{{ ucfirst(__('laravel-crm::lang.download')) }}</a>
                @endcan
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm deliveries')
                    <a href="{{ url(route('laravel-crm.deliveries.edit', $delivery)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm deliveries')
                    <form action="{{ route('laravel-crm.deliveries.destroy', $delivery) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.delivery') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row card-show card-fa-w30">
            <div class="col-sm-6 bdelivery-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-4 text-right">{{ ucfirst(__('laravel-crm::lang.delivery_expected')) }}</dt>
                    <dd class="col-sm-8">
                        {{ $delivery->delivery_expected  ?? null }}
                    </dd>
                    <dt class="col-sm-4 text-right">{{ ucfirst(__('laravel-crm::lang.delivered_on')) }}</dt>
                    <dd class="col-sm-8">
                        {{ $delivery->delivered_on  ?? null }}
                    </dd>
                   @foreach($addresses as $address)
                        <dt class="col-sm-4 text-right">{{ ($address->addressType) ? ucfirst($address->addressType->name).' ' : null }}{{ ucfirst(__('laravel-crm::lang.address')) }}</dt>
                        <dd class="col-sm-8">
                            {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) }} {{ ($address->primary) ? '(Primary)' : null }}
                            @if($address->contact)
                                <small><br >{{ ucwords(__('laravel-crm::lang.contact')) }}: {{ $address->contact }}</small>
                            @endif
                            @if($address->phone)
                                <small><br >{{ ucwords(__('laravel-crm::lang.phone')) }}: {{ $address->phone }}</small>
                            @endif
                        </dd>
                    @endforeach
                </dl>
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.delivery_items')) }} ({{ $delivery->deliveryProducts->count() }})</span></h6>
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
                    @foreach($delivery->deliveryProducts()->get() as $deliveryProduct)
                        <tr>
                            <td>{{ $deliveryProduct->orderProduct->product->name }}</td>
                            <td>{{ money($deliveryProduct->orderProduct->price ?? null, $deliveryProduct->orderProduct->currency) }}</td>
                            <td>{{ $deliveryProduct->orderProduct->quantity }}</td>
                            <td>{{ money($deliveryProduct->orderProduct->amount ?? null, $deliveryProduct->orderProduct->currency) }}</td>
                        </tr>
                        @if($deliveryProduct->orderProduct->comments)
                            <tr>
                                <td colspan="4" class="b-0 pt-0">
                                    <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong><br />
                                    {{ $deliveryProduct->orderProduct->comments }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
                @endcan
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $delivery
                ])
            </div>
        </div>

    @endcomponent

@endcomponent
