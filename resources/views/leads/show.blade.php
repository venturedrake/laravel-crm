@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $lead->title }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> Back to leads</a>
                <a href="{{ url(route('laravel-crm.leads.edit', $lead)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
            </span>
        </div>
        <div class="card-body card-show">
            <div class="row">
                <div class="col-sm-6 border-right">
                    <h6 class="text-uppercase">Details</h6>
                    <hr />
                    <p><span class="fa fa-dollar" aria-hidden="true"></span> {{ money($lead->amount, $lead->currency) }}</p>
                    <p><span class="fa fa-info" aria-hidden="true"></span> {{ $lead->description }}</p>
                    <p><span class="fa fa-user-circle" aria-hidden="true"></span> {{ $lead->assignedToUser->name }}</p>
                    <h6 class="mt-4 text-uppercase"> Person</h6>
                    <hr />
                    <p><span class="fa fa-user" aria-hidden="true"></span> {{ $lead->person_name }} </p>
                    @if($email)
                        <p><span class="fa fa-envelope" aria-hidden="true"></span> <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</p>
                    @endif
                    @if($phone)
                        <p><span class="fa fa-phone" aria-hidden="true"></span> <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</p>
                    @endif
                    <h6 class="mt-4 text-uppercase"> Organisation</h6>
                    <hr />
                    <p><span class="fa fa-building" aria-hidden="true"></span> {{ $lead->organisation_name }}</p>
                    <p><span class="fa fa-map-marker" aria-hidden="true"></span> {{ ($address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }} </p>
                </div>
                <div class="col-sm-6">
                    <h6 class="text-uppercase">Activities</h6>
                    <hr />
                    ...
                </div>
            </div>
        </div>
    </div>

@endsection