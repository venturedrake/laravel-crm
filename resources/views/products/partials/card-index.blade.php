@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            Products
        @endslot
    
        @slot('actions')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.products.create')) }}"><span class="fa fa-plus"></span>  Add product</a></span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Code</th>
                <th scope="col">Category</th>
                <th scope="col">Unit</th>
                <th scope="col">Price ({{ \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD' }})</th>
                <th scope="col">Tax %</th>
                <th scope="col">Active</th>
                <th scope="col">Owner</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.products.show',$product)) }}">
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->code }}</td>
                    <td>{{ $product->productCategory->name ?? null }}</td>
                    <td>{{ $product->unit }}</td>
                    <td>{{ (isset($product->getDefaultPrice()->unit_price)) ? money($product->getDefaultPrice()->unit_price ?? null, $product->getDefaultPrice()->currency) : null }}</td>
                    <td>{{ $product->tax_rate }}</td>
                    <td>{{ ($product->active == 1) ? 'YES' : 'NO' }}</td>
                    <td>{{ $product->ownerUser->name ?? null }}</td>
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
        
    @endcomponent
    
    @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $products->links() }}
        @endcomponent
    @endif
    
@endcomponent    