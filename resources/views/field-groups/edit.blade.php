@extends('laravel-crm::layouts.app')

@section('content')

    <form method="POST" action="{{ url(route('laravel-crm.field-groups.update', $fieldGroup)) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-header">
                @include('laravel-crm::layouts.partials.nav-settings')
            </div>
            <div class="card-body">
                <h3 class="mb-3"> {{ ucfirst(trans('laravel-crm::lang.edit_field_group')) }} <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.field-groups.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(trans('laravel-crm::lang.back_to_field_groups')) }}</a></span></h3>
                @include('laravel-crm::field-groups.partials.fields')
            </div>
            @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.fields.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(trans('laravel-crm::lang.cancel')) }}</a>
                <button type="submit" class="btn btn-primary">{{ ucwords(trans('laravel-crm::lang.save_changes')) }}</button>
            @endcomponent
        </div>
    </form>

@endsection