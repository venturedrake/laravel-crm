@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Laravel CRM Updates</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.teams.create')) }}"><span class="fa fa-plus"></span> Add team</a></span></div>
        <div class="card-body">
            <p class="card-text">Current version {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value }} {{ (\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value == \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value) ? 'is the latest version' : null }}.</p>
            @if(\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value < \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value)
                <hr />
                <h5 class="mb-4"> Updated version of Laravel CRM is available.</h5>
                <p class="card-text">You can update from Laravel CRM {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value }} to {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value }}</p>
                <a type="button" class="btn btn-primary btn-sm" href="https://github.com/venturedrake/laravel-crm" target="_blank">Upgrade Guide</a>
            @endif    
        </div>
    </div>

@endsection