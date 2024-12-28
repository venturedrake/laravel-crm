@extends('laravel-crm::layouts.app')

@section('content')

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
                        <h3 class="card-title float-left m-0"> {{ ucfirst(__('laravel-crm::lang.product_categories')) }} </h3> @can('create crm product categories')<span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.product-categories.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_product_category')) }}</a></span>@endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-pane active" id="roles" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table mb-0 card-table table-hover">
                                    <thead>
                                    <tr>
                                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.products')) }}</th>
                                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.updated')) }}</th>
                                        <th scope="col"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($productCategories as $productCategory)
                                        <tr class="has-link" data-url="{{ url(route('laravel-crm.product-categories.show',$productCategory)) }}">
                                            <td>{{ $productCategory->name }}</td>
                                            <td>{{ $productCategory->products->count() }}</td>
                                            <td>{{ $productCategory->created_at->format($dateFormat) }}</td>
                                            <td>{{ $productCategory->updated_at->format($dateFormat) }}</td>
                                            <td class="disable-link text-right">
                                                @can('view crm product categories')
                                                    <a href="{{  route('laravel-crm.product-categories.show',$productCategory) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                                @endcan
                                                @can('edit crm product categories')
                                                    <a href="{{  route('laravel-crm.product-categories.edit',$productCategory) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                                @endcan
                                                @can('delete crm product categories')
                                                    <form action="{{ route('laravel-crm.product-categories.destroy',$productCategory) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                                        {{ method_field('DELETE') }}
                                                        {{ csrf_field() }}
                                                        <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.product_category') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if($productCategories instanceof \Illuminate\Pagination\LengthAwarePaginator )
                        @component('laravel-crm::components.card-footer')
                            {{ $productCategories->links() }}
                        @endcomponent
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection