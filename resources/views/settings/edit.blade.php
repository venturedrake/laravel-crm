@extends('laravel-crm::layouts.app')

@section('content')
<form method="POST" action="{{ url(route('laravel-crm.settings.update')) }}">
    @csrf
    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="settings" role="tabpanel">
                    @include('laravel-crm::settings.partials.fields')
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</form>
@endsection