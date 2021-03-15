@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $lead->title }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> Back to leads</a> | 
                <a href="{{ route('laravel-crm.leads.convert-to-deal',$lead) }}" class="btn btn-success btn-sm">Convert</a>
                @include('laravel-crm::partials.navs.activities') |
                <a href="{{ url(route('laravel-crm.leads.edit', $lead)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                <form action="{{ route('laravel-crm.leads.destroy',$lead) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="lead"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
</span>
        </div>
        <div class="card-body card-show card-fa-w30">
            <div class="row">
                <div class="col-sm-6 border-right">
                    <h6 class="text-uppercase">Details</h6>
                    <hr />
                    <p><span class="fa fa-tag" aria-hidden="true"></span>@include('laravel-crm::partials.labels',[
                            'labels' => $lead->labels
                    ])</p>
                    <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($lead->amount, $lead->currency) }}</p>
                    <p><span class="fa fa-info" aria-hidden="true"></span> {{ $lead->description }}</p>
                    <p><span class="fa fa-user-circle" aria-hidden="true"></span> {{ $lead->assignedToUser->name }}</p>
                    <h6 class="mt-4 text-uppercase"> Person</h6>
                    <hr />
                    <p><span class="fa fa-user" aria-hidden="true"></span> {{ $lead->person->name ?? $lead->person_name }} </p>
                    @if($email)
                        <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                    @endif
                    @if($phone)
                        <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                    @endif
                    <h6 class="mt-4 text-uppercase"> Organisation</h6>
                    <hr />
                    <p><span class="fa fa-building" aria-hidden="true"></span> {{ $lead->organisation->name ?? $lead->organisation_name }}</p>
                    <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }} </p>
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
        </div>
    </div>

@endsection