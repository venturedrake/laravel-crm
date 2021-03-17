@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">Products</h3> <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.products.create')) }}"><span class="fa fa-plus"></span> Add product</a></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0 card-table table-hover">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Category</th>
                    <th scope="col">Price</th>
                    <th scope="col">Tax</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Owner</th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                    <tr class="has-link" data-url="{{ url(route('laravel-crm.products.show',$product)) }}">
                        <td>{{ $product->name }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="disable-link text-right">
                            <a href="{{  route('laravel-crm.products.show',$product) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                            <a href="{{  route('laravel-crm.products.edit',$product) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                            <form action="{{ route('laravel-crm.products.destroy',$product) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                                <button class="btn btn-danger btn-sm" type="submit" data-model="product"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator )
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        @endif
        </div>
    </div>

@endsection