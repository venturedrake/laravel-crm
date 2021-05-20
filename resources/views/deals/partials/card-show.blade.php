@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $deal->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.deals.index')) }}"><span class="fa fa-angle-double-left"></span> Back to deals</a> | 
                @if(!$deal->closed_at)
                    <a href="{{  route('laravel-crm.deals.won',$deal) }}" class="btn btn-success btn-sm">Won</a>
                    <a href="{{  route('laravel-crm.deals.lost',$deal) }}" class="btn btn-danger btn-sm">Lost</a>
                @else
                    <a href="{{  route('laravel-crm.deals.reopen',$deal) }}" class="btn btn-outline-secondary btn-sm">Reopen</a>
                @endif
                @include('laravel-crm::partials.navs.activities') |
                <a href="{{ url(route('laravel-crm.deals.edit', $deal)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                <form action="{{ route('laravel-crm.deals.destroy',$deal) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="deal"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row card-show card-fa-w30">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">Details</h6>
                <hr />
                <p><span class="fa fa-tag" aria-hidden="true"></span>@include('laravel-crm::partials.labels',[
                            'labels' => $deal->labels
                    ])</p>
                <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($deal->amount, $deal->currency) }}</p>
                <p><span class="fa fa-info" aria-hidden="true"></span> {{ $deal->description }}</p>
                <p><span class="fa fa-user-circle" aria-hidden="true"></span> {{ $deal->assignedToUser->name }}</p>
                <h6 class="mt-4 text-uppercase"> Contact Person</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> {{ $deal->person->name ?? null }} </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                <h6 class="mt-4 text-uppercase"> Organisation</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> {{ $deal->organisation->name ?? null }}</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>

                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>Products ({{ $deal->dealProducts->count() }})</span></h6>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Price</th>
                         <th scope="col">Quantity</th>
                        <th scope="col">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($deal->dealProducts()->whereNotNull('product_id')->get() as $dealProduct)
                        <tr>
                            <td>{{ $dealProduct->product->name }}</td>
                            <td>{{ money($dealProduct->price ?? null, $dealProduct->currency) }}</td>
                            <td>{{ $dealProduct->quantity }}</td>
                            <th>{{ money($dealProduct->amount ?? null, $dealProduct->currency) }}</th>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="col-sm-6">
                <h6 class="text-uppercase">Notes</h6>
                <hr />
                ...
                <h6 class="text-uppercase mt-4">Files</h6>
                <hr />
                ...
                <h6 class="text-uppercase mt-4">Activities</h6>
                <hr />
                ...
            </div>
        </div>

    @endcomponent

@endcomponent    