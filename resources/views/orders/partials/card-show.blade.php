@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $order->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.orders.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_orders')) }}</a> | 
                @can('edit crm orders')
                @if(!$order->closed_at)
                    <a href="{{  route('laravel-crm.orders.won',$order) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.won')) }}</a>
                    <a href="{{  route('laravel-crm.orders.lost',$order) }}" class="btn btn-danger btn-sm">{{ ucfirst(__('laravel-crm::lang.lost')) }}</a>
                @else
                    <a href="{{  route('laravel-crm.orders.reopen',$order) }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.reopen')) }}</a>
                @endif
                @endcan
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm orders')
                <a href="{{ url(route('laravel-crm.orders.edit', $order)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm orders')
                <form action="{{ route('laravel-crm.orders.destroy',$order) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
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
                <p><span class="fa fa-tag" aria-hidden="true"></span>@include('laravel-crm::partials.labels',[
                            'labels' => $order->labels
                    ])</p>
                <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($order->amount, $order->currency) }}</p>
                <p><span class="fa fa-info" aria-hidden="true"></span> {{ $order->description }}</p>
                <p><span class="fa fa-user-circle" aria-hidden="true"></span> <a href="{{ route('laravel-crm.users.show', $order->ownerUser) }}">{{ $order->ownerUser->name ?? null }}</a></p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> {{ $order->person->name ?? null }} </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> {{ $order->organisation->name ?? null }}</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.products')) }} ({{ $order->orderProducts->count() }})</span></h6>
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
                            <th>{{ money($orderProduct->amount ?? null, $orderProduct->currency) }}</th>
                        </tr>
                    @endforeach
                    </tbody>
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