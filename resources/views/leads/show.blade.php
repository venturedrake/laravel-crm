@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $lead->title }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> Back to leads</a>
                <a href="{{ url(route('laravel-crm.leads.edit', $lead)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <h6><span class="fa fa-dollar" aria-hidden="true"></span> Details</h6>
                    <hr />
                    {{ $lead->amount }}
                    <h6 class="mt-4"><span class="fa fa-user" aria-hidden="true"></span> Person</h6>
                    <hr />
                    {{ $lead->person_name }}
                    <h6 class="mt-4"><span class="fa fa-building" aria-hidden="true"></span> Organisation</h6>
                    <hr />
                    {{ $lead->organisation_name }}
                </div>
                <div class="col-sm-6">
                    <h6><span class="fa fa-info" aria-hidden="true"></span> Activities</h6>
                    <hr />
                    ...
                </div>
            </div>
        </div>
    </div>

@endsection