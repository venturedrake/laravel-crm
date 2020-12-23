@extends('laravel-crm::layouts.app')

@section('content')

    <form method="POST" action="{{ url(route('laravel-crm.leads.store')) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title float-left m-0">Create lead</h3> <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> Back to leads</a></span></div>
            <div class="card-body">
                
                    @include('laravel-crm::partials.form.text',['name' => 'person_name', 'title' => 'Contact person'])
                    @include('laravel-crm::partials.form.text',['name' => 'organisation_name', 'title' => 'Organisation'])
                    @include('laravel-crm::partials.form.text',['name' => 'title', 'title' => 'Title'])
                    @include('laravel-crm::partials.form.textarea',['name' => 'title', 'title' => 'Description', 'rows' => 5])
                
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.leads.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>

@endsection