@extends('laravel-crm::layouts.app')

@section('content')
<form method="POST" action="{{ url(route('laravel-crm.settings.update')) }}" enctype="multipart/form-data">
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
                        <h3 class="card-title float-left m-0">{{ ucwords(__('laravel-crm::lang.general_settings')) }}</h3>
                    </div>
                    <div class="card-body">
                        @include('laravel-crm::settings.partials.fields')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">{{ ucwords(trans('laravel-crm::lang.save_changes')) }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection