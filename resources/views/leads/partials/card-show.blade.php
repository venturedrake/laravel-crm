@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $lead->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $lead,
                    'route' => 'leads'
                ]) | 
                @can('edit crm leads')
                <a href="{{ route('laravel-crm.leads.convert-to-deal',$lead) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.convert')) }}</a>
                @endcan
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm leads')
                <a href="{{ url(route('laravel-crm.leads.edit', $lead)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm leads')
                <form action="{{ route('laravel-crm.leads.destroy',$lead) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.lead') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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
                            'labels' => $lead->labels
                    ])</p>
                <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($lead->amount, $lead->currency) }}</p>
                <p><span class="fa fa-info" aria-hidden="true"></span> {{ $lead->description }}</p>
                <p><span class="fa fa-user-circle" aria-hidden="true"></span> <a href="{{ route('laravel-crm.users.show', $lead->ownerUser) }}">{{ $lead->ownerUser->name ?? null }}</a></p>
                <h6 class="mt-4 text-uppercase"> {{ ucfirst(__('laravel-crm::lang.client')) }}</h6>
                <hr />
                <p><span class="fa fa-address-card" aria-hidden="true"></span> @if($lead->client)<a href="{{ route('laravel-crm.clients.show',$lead->client) }}">{{ $lead->client->name }}</a>@endif</p>
                <h6 class="mt-4 text-uppercase"> {{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> @if($lead->organisation)<a href="{{ route('laravel-crm.organisations.show',$lead->organisation) }}">{{ $lead->organisation->name }}</a>@endif</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }} </p>
                <h6 class="mt-4 text-uppercase"> {{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> @if($lead->person)<a href="{{ route('laravel-crm.people.show',$lead->person) }}">{{ $lead->person->name }}</a>@endif</p>
                @if($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endif
                @if($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endif
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $lead
                ])
            </div>
        </div>
        
    @endcomponent

@endcomponent    