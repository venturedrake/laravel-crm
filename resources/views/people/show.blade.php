@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $person->name }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.people.index')) }}"><span class="fa fa-angle-double-left"></span> Back to people</a>
                <a href="#" class="btn btn-success btn-sm"><span class="fa fa-plus" aria-hidden="true"></span> Add new deal</a>
                <a href="{{ url(route('laravel-crm.people.edit', $person)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                <form action="{{ route('laravel-crm.people.destroy',$person) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="person"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span>
        </div>
        <div class="card-body card-show">
            <div class="row">
                <div class="col-sm-6 border-right">
                    <h6 class="text-uppercase">Details</h6>
                    <hr />
                    <dl class="row">
                        <dt class="col-sm-3 text-right">First name</dt>
                        <dd class="col-sm-9">{{ $person->first_name }}</dd>
                        <dt class="col-sm-3 text-right">Middle name</dt>
                        <dd class="col-sm-9">{{ $person->middle_name }}</dd>
                        <dt class="col-sm-3 text-right">Last name</dt>
                        <dd class="col-sm-9">{{ $person->last_name }}</dd>
                        <dt class="col-sm-3 text-right">Email</dt>
                        <dd class="col-sm-9"><a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})</dd>
                        <dt class="col-sm-3 text-right">Phone</dt>
                        <dd class="col-sm-9"><a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})</dd>
                    </dl>
                    <h6 class="mt-4 text-uppercase"> Organisation</h6>
                    <hr />
                    <dl class="row">
                        <dt class="col-sm-3 text-right"><span class="fa fa-building" aria-hidden="true"></span></dt>
                        <dd class="col-sm-9">{{ $organisation->name ?? null }}</dd>
                        <dt class="col-sm-3 text-right">Address</dt>
                        <dd class="col-sm-9">{{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }}</dd>
                    </dl>
                    <h6 class="text-uppercase mt-4">Deals</h6>
                    <hr />
                    ...
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