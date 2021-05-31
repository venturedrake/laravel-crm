@extends('laravel-crm::layouts.app')

@section('content')
    <form method="POST" action="{{ url(route('laravel-crm.roles.store')) }}">
        @csrf
        <div class="card">
            <div class="card-header">
                @include('laravel-crm::layouts.partials.nav-settings')
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="roles" role="tabpanel">
                        <h3 class="mb-3">{{ ucfirst(__('laravel-crm::lang.create_role')) }} <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.roles.index')) }}"><span class="fa fa-angle-double-left"></span>  {{ ucfirst(__('laravel-crm::lang.back_to_roles')) }}</a>
                        </span></h3>
                        @include('laravel-crm::roles.partials.fields')
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.roles.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</a>
                <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
            </div>
        </div>
    </form>
@endsection