@extends('laravel-crm::layouts.app')

@section('content')
    <form method="POST" action="{{ url(route('laravel-crm.leads.update', $lead)) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-header"><h3 class="card-title float-left m-0">Edit Lead</h3>
                <span class="float-right">
                    <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> Back to leads</a></span></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'person_name',
                           'label' => 'Contact person',
                           'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
                       ])
                    </div>
                    <div class="col-sm-6">
                        
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.leads.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
@endsection