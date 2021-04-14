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
                        <h3 class="mb-3">Create Role <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.roles.index')) }}"><span class="fa fa-angle-double-left"></span> Back to roles</a>
                        </span></h3>
                        @include('laravel-crm::roles.partials.fields')
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.roles.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
@endsection