<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.product_categories')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_product_category')) }}" link="{{ url(route('laravel-crm.product-categories.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$productCategories" link="/product-categories/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('actions', $productCategory)
                @can('view crm product categories')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.product-categories.show', $productCategory)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm product categories')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.product-categories.edit', $productCategory)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm product categories')
                    <x-mary-button onclick="modalDeleteProductCategory{{ $productCategory->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="productCategory" id="{{ $productCategory->id }}" deleting="product category"  />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
