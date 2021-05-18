@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $product->name }} 
        @endslot

        @slot('actions')

            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.products.index')) }}"><span class="fa fa-angle-double-left"></span> Back to products</a> |
                <a href="{{ url(route('laravel-crm.products.edit', $product)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('laravel-crm.products.destroy',$product) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="person"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span>
            
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">Details</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">Product code</dt>
                    <dd class="col-sm-9">{{ $product->code }}</dd>
                    <dt class="col-sm-3 text-right">Unit</dt>
                    <dd class="col-sm-9">{{ $product->unit }}</dd>
                    <dt class="col-sm-3 text-right">Tax %</dt>
                    <dd class="col-sm-9">{{ $product->tax_rate }}</dd>
                    <dt class="col-sm-3 text-right">Category</dt>
                    <dd class="col-sm-9">{{ $product->productCategory->name ?? null }}</dd>
                    <dt class="col-sm-3 text-right">Description</dt>
                    <dd class="col-sm-9">{{ $product->description }}</dd>
                </dl>
                <h6 class="text-uppercase mt-4">Owner</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">Name</dt>
                    <dd class="col-sm-9">{{ $product->ownerUser->name }}</dd>
                </dl>
            </div>
            <div class="col-sm-6">
                <h6 class="text-uppercase">Prices</h6>
                <hr />
                <h6 class="text-uppercase mt-4">Variations</h6>
                <hr />
                <h6 class="text-uppercase mt-4">Deals</h6>
                <hr />
            </div>
        </div>
        
    @endcomponent    

@endcomponent    