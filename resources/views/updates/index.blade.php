@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title m-0">Laravel CRM {{ ucfirst(__('laravel-crm::lang.updates')) }}</h3></div>
        <div class="card-body">
            <p class="card-text">{{ ucfirst(__('laravel-crm::lang.current_version')) }} {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value }} {{ (\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value == \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value) ? __('laravel-crm::lang.is_the_latest_version') : null }}</p>
            @if(\VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value < \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value)
                <hr />
                <h5 class="mb-4">{{ ucfirst(__('laravel-crm::lang.updated_version_of_laravel_crm_is_available')) }}</h5>
                <p class="card-text">{{ ucfirst(__('laravel-crm::lang.you_can_update_from_laravel_crm')) }} {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version')->first()->value }} to {{ \VentureDrake\LaravelCrm\Models\Setting::where('name','version_latest')->first()->value }}</p>
                <a type="button" class="btn btn-primary btn-sm" href="https://github.com/venturedrake/laravel-crm" target="_blank">{{ ucfirst(__('laravel-crm::lang.upgrade_guide')) }}</a>
            @endif    
        </div>
    </div>

@endsection