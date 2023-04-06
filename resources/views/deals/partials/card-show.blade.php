@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $deal->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $deal,
                    'route' => 'deals'
                ]) | 
                @can('edit crm deals')
                @if(!$deal->closed_at)
                    <a href="{{  route('laravel-crm.deals.won',$deal) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.won')) }}</a>
                    <a href="{{  route('laravel-crm.deals.lost',$deal) }}" class="btn btn-danger btn-sm">{{ ucfirst(__('laravel-crm::lang.lost')) }}</a>
                @else
                    <a href="{{  route('laravel-crm.deals.reopen',$deal) }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.reopen')) }}</a>
                @endif
                @endcan
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm deals')
                <a href="{{ url(route('laravel-crm.deals.edit', $deal)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm deals')
                <form action="{{ route('laravel-crm.deals.destroy',$deal) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.deal') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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
                            'labels' => $deal->labels
                    ])</p>
                <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($deal->amount, $deal->currency) }}</p>
                <p><span class="fa fa-info" aria-hidden="true"></span> {{ $deal->description }}</p>
                <p><span class="fa fa-user-circle" aria-hidden="true"></span> <a href="{{ route('laravel-crm.users.show', $deal->ownerUser) }}">{{ $deal->ownerUser->name ?? null }}</a></p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.client')) }}</h6>
                <hr />
                <p><span class="fa fa-address-card" aria-hidden="true"></span> @if($deal->client)<a href="{{ route('laravel-crm.clients.show',$deal->client) }}">{{ $deal->client->name }}</a>@endif </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> @if($deal->organisation)<a href="{{ route('laravel-crm.organisations.show',$deal->organisation) }}">{{ $deal->organisation->name }}</a>@endif</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }} </p>
                <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> @if($deal->person)<a href="{{ route('laravel-crm.people.show',$deal->person) }}">{{ $deal->person->name }}</a>@endif </p>
                @isset($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endisset
                @isset($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endisset
                @can('view crm products')
                <h6 class="text-uppercase mt-4 section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.products')) }} ({{ $deal->dealProducts->count() }})</span></h6>
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
                @endcan    
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $deal
                ])
            </div>
        </div>

    @endcomponent

@endcomponent    