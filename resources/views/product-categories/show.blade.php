@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <h3 class="mb-3"> {{ $productCategory->name }} <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.product-categories.index')) }}"><span class="fa fa-angle-double-left"></span> Back to product categories</a> | 
                <a href="{{ url(route('laravel-crm.product-categories.edit', $productCategory)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('laravel-crm.product-categories.destroy',$productCategory) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="productCategory"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span></h3>

            <div class="row">
                <div class="col-sm-6 border-right">
                    <h6 class="text-uppercase">Details</h6>
                    <hr />
                    <dl class="row">
                        <dt class="col-sm-3 text-right">Description</dt>
                        <dd class="col-sm-9">{{ $productCategory->description }}</dd>
                    </dl>

                </div>
                <div class="col-sm-6">
                    <h6 class="text-uppercase section-h6-title-table"><span>Products ({{ $productCategory->products->count() }})</span></h6>
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col" width="120">Price ({{ \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD' }})</th>
                            <th scope="col" width="80">Tax %</th>
                            <th scope="col" width="80">Active</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($productCategory->products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ (isset($product->getDefaultPrice()->unit_price)) ? money($product->getDefaultPrice()->unit_price ?? null, $product->getDefaultPrice()->currency) : null }}</td>
                                <td>{{ $product->tax_rate }}</td>
                                <td>{{ ($product->active == 1) ? 'YES' : 'NO' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @component('laravel-crm::components.card-footer')
            <a href="{{ url(route('laravel-crm.product-categories.index')) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        @endcomponent
    </div>
@endsection