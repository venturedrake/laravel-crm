@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $lead->title }}
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_leads')) }}</a> | 
                @can('edit crm leads')
                <a href="{{ route('laravel-crm.leads.convert-to-deal',$lead) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.convert')) }}</a> |
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
                <h6 class="mt-4 text-uppercase"> {{ ucfirst(__('laravel-crm::lang.person')) }}</h6>
                <hr />
                <p><span class="fa fa-user" aria-hidden="true"></span> {{ $lead->person->name ?? null}} </p>
                @if($email)
                    <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                @endif
                @if($phone)
                    <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                @endif
                <h6 class="mt-4 text-uppercase"> {{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <p><span class="fa fa-building" aria-hidden="true"></span> {{ $lead->organisation->name ?? null }}</p>
                <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }} </p>
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.activities', [
                    'model' => $lead
                ])
            </div>
        </div>
        
    @endcomponent

@endcomponent    