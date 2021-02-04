@extends('laravel-crm::layouts.app')

@section('content')
    <form method="POST" action="{{ url(route('laravel-crm.people.update', $person)) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-header"><h3 class="card-title float-left m-0">Edit person</h3>
                <span class="float-right">
                    <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.people.index')) }}"><span class="fa fa-angle-double-left"></span> Back to people</a></span></div>
            <div class="card-body">
                @include('laravel-crm::people.partials.fields')
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.people.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
@endsection