@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">Edit Lead</div>
        <div class="card-body">
            <form method="POST" action="{{ url(route('laravel-crm.leads.update')) }}">
                @csrf
                @method('PUT')
                ...
            </form>
        </div>
    </div>

@endsection