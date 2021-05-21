@extends('laravel-crm::layouts.app')

@section('content')

    <form method="POST" action="{{ url(route('laravel-crm.product-categories.update', $productCategory)) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-header">
                @include('laravel-crm::layouts.partials.nav-settings')
            </div>
            <div class="card-body">
                <h3 class="mb-3"> Edit product category <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.product-categories.index')) }}"><span class="fa fa-angle-double-left"></span> Back to product categories</a></span></h3>
                @include('laravel-crm::product-categories.partials.fields')
            </div>
            @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.product-categories.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            @endcomponent
        </div>
    </form>

@endsection