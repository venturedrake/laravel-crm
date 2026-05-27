@extends('laravel-crm::layouts.portal')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <livewire:crm-portal-feature-show :feature="$feature" />
    </div>
@endsection
