@extends('laravel-crm::layouts.app')

@section('content')

<form method="POST" action="{{ url(route('laravel-crm.field-groups.store')) }}">
    @csrf
    <div class="container-fluid pl-0">
        <div class="row">
            <div class="col col-md-2">
                <div class="card">
                    <div class="card-body py-3 px-2">
                        @include('laravel-crm::layouts.partials.nav-settings')
                    </div>
                </div>
            </div>
            <div class="col col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title float-left m-0">{{ ucfirst(trans('laravel-crm::lang.create_custom_field_group')) }}</h3>
                        <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.field-groups.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(trans('laravel-crm::lang.back_to_custom_field_groups')) }}</a></span>
                    </div>
                    <div class="card-body">
                        @include('laravel-crm::field-groups.partials.fields')
                    </div>
                    @component('laravel-crm::components.card-footer')
                        <a href="{{ url(route('laravel-crm.field-groups.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(trans('laravel-crm::lang.cancel')) }}</a>
                        <button type="submit" class="btn btn-primary">{{ ucfirst(trans('laravel-crm::lang.save')) }}</button>
                    @endcomponent
                </div>
            </div>
        </div>
    </div>
</form>
    
@endsection