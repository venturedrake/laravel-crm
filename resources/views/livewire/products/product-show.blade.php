<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $product->name }}" class="mb-5" progress-indicator >
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_products')) }}" link="{{ url(route('laravel-crm.products.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
            |
            @can('edit crm products')
                <x-mary-button link="{{ url(route('laravel-crm.products.edit', $product)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm products')
                <x-mary-button onclick="modalDeleteProduct{{ $product->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="product" id="{{ $product->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ strtoupper(__('laravel-crm::lang.sku')) }}</strong>
                        <span>
                        {{ $product->code }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.barcode')) }}</strong>
                        <span>
                        {{ $product->barcode }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.purchase_account')) }}</strong>
                        <span>
                        {{ $product->purchase_account }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.sales_account')) }}</strong>
                        <span>
                        {{ $product->sales_account }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.unit')) }}</strong>
                        <span>
                        {{ $product->unit }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.tax_rate')) }}</strong>
                        <span>
                        {{ $product->taxRate->name ?? null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.tax_rate_percent')) }}</strong>
                        <span>
                        {{ $product->tax_rate ?? $product->taxRate->rate ?? 0 }}%
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.category')) }}</strong>
                        <span>
                        {{ $product->productCategory->name ?? null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $product->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucwords(__('laravel-crm::lang.integrations')) }}</strong>
                        <span>
                        @if($product->xeroItem)<img src="/vendor/laravel-crm/img/xero-icon.png" height="20" />@endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $product->ownerUser)<a href="{{ route('laravel-crm.users.show', $product->ownerUser) }}" class="link link-hover link-primary">{{ $product->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.prices')) }}" shadow separator>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="px-0 pt-0">{{ ucwords(__('laravel-crm::lang.unit_price')) }}</th>
                           {{-- <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.cost_per_unit')) }}</th>
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.direct_cost')) }}</th>--}}
                            <th class="px-0 pt-0">{{ ucfirst(__('laravel-crm::lang.currency')) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($product->productPrices as $productPrice)
                            <tr>
                                <td class="px-0">{{ money($productPrice->unit_price ?? null, $productPrice->currency) }}</td>
                                {{-- <td>{{ money($productPrice->cost_per_unit ?? null, $productPrice->cost_per_unit) }}</td>
                                 <td>{{ money($productPrice->direct_cost ?? null, $productPrice->direct_cost) }}</td>--}}
                                <td class="px-0">{{ $productPrice->currency }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
            {{--<x-mary-card title="{{ ucfirst(__('laravel-crm::lang.variations')) }}" shadow separator>
                
            </x-mary-card>
            @can('view crm deals')
                <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.deals')) }}" shadow separator>
                    
                </x-mary-card>
            @endcan--}}
        </div>
    </div>
</div>
