@extends('laravel-crm::layouts.app')

@section('content')
    <form method="POST" action="{{ url(route('laravel-crm.roles.store')) }}">
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
                            <h3 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.create_role')) }}</h3> <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.roles.index')) }}"><span class="fa fa-angle-double-left"></span>  {{ ucfirst(__('laravel-crm::lang.back_to_roles')) }}</a>
                        </span>
                        </div>
                        <div class="card-body">
                            @include('laravel-crm::roles.partials.fields')
                        </div>
                        <div class="card-footer">
                            <a href="{{ url(route('laravel-crm.roles.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
                            <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection