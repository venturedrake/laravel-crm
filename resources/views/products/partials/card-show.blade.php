@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $product->name }} 
        @endslot

        @slot('actions')

            <span class="float-right">
                @include('laravel-crm::partials.return-button',[
                    'model' => $product,
                    'route' => 'products'
                ]) | 
                @can('edit crm products')
                <a href="{{ url(route('laravel-crm.products.edit', $product)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm products')
                <form action="{{ route('laravel-crm.products.destroy',$product) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.product') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan    
            </span>
            
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')

        <div class="row">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.product_code')) }}</dt>
                    <dd class="col-sm-9">{{ $product->code }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.unit')) }}</dt>
                    <dd class="col-sm-9">{{ $product->unit }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.tax')) }} %</dt>
                    <dd class="col-sm-9">{{ $product->tax_rate }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.category')) }}</dt>
                    <dd class="col-sm-9">{{ $product->productCategory->name ?? null }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.description')) }}</dt>
                    <dd class="col-sm-9">{{ $product->description }}</dd>
                </dl>
                <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.owner')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.name')) }}</dt>
                    <dd class="col-sm-9">{{ $product->ownerUser->name }}</dd>
                </dl>
            </div>
            <div class="col-sm-6">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.prices')) }}</h6>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">{{ ucwords(__('laravel-crm::lang.unit_price')) }}</th>
                       {{-- <th scope="col">{{ ucwords(__('laravel-crm::lang.cost_per_unit')) }}</th>
                        <th scope="col">{{ ucwords(__('laravel-crm::lang.direct_cost')) }}</th>--}}
                        <th scope="col">{{ ucwords(__('laravel-crm::lang.currency')) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($product->productPrices as $productPrice)
                        <tr>
                            <th>{{ money($productPrice->unit_price ?? null, $productPrice->currency) }}</th>
                           {{-- <td>{{ money($productPrice->cost_per_unit ?? null, $productPrice->cost_per_unit) }}</td>
                            <td>{{ money($productPrice->direct_cost ?? null, $productPrice->direct_cost) }}</td>--}}
                            <td>{{ $productPrice->currency }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
               
                <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.variations')) }}</h6>
                <hr />
                ...
                @can('view crm deals')
                <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.deals')) }}</h6>
                <hr />
                ...
                @endcan    
            </div>
        </div>
        
    @endcomponent    

@endcomponent    