@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Leads</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.leads.create')) }}"><span class="fa fa-plus"></span>  Add lead</a></span></div>
        <div class="card-body">
            ...
        </div>
    </div>

@endsection