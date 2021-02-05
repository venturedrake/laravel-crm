@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $product->title }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.products.index')) }}"><span class="fa fa-angle-double-left"></span> Back to products</a>
                <a href="{{ url(route('laravel-crm.products.edit', $product)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
            </span>
        </div>
        <div class="card-body card-show">
            <div class="row">
                <div class="col-sm-6 border-right">
                
                </div>
                <div class="col-sm-6">
                    
                </div>
            </div>
        </div>
    </div>

@endsection