@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <h3 class="mb-3"> {{ $taxRate->name }} <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.tax-rates.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_tax_rates')) }}</a> | 
                @can('edit crm tax rates')
                <a href="{{ url(route('laravel-crm.tax-rates.edit', $taxRate)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm tax rates')    
                <form action="{{ route('laravel-crm.tax-rates.destroy',$taxRate) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.tax_rate') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span></h3>

            <div class="row">
                <div class="col-sm-6 border-right">
                    <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                    <hr />
                    <dl class="row">
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.rate')) }}</dt>
                        <dd class="col-sm-9">{{ $taxRate->rate }}%</dd>
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.description')) }}</dt>
                        <dd class="col-sm-9">{{ $taxRate->description }}</dd>
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.default_tax_rate')) }}</dt>
                        <dd class="col-sm-9">{{ $taxRate->default == 1 ? 'YES' : 'NO' }}</dd>
                    </dl>
                </div>
                <div class="col-sm-6">
                    @can('view crm products')
                    <h6 class="text-uppercase section-h6-title-table"><span>{{ ucfirst(__('laravel-crm::lang.products')) }} ({{ $taxRate->products->count() }})</span></h6>
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.item')) }}</th>
                            <th scope="col" width="80">{{ ucfirst(__('laravel-crm::lang.active')) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($taxRate->products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ ($product->active == 1) ? 'YES' : 'NO' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endcan    
                </div>
            </div>
        </div>
    </div>
@endsection