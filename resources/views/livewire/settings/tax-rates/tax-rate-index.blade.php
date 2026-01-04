<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.tax_rates')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_tax_rate')) }}" link="{{ url(route('laravel-crm.tax-rates.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$taxRates" link="/tax-rates/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('actions', $taxRate)
                @can('view crm tax rates')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.tax-rates.show', $taxRate)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm tax rates')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.tax-rates.edit', $taxRate)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm tax rates')
                    <x-mary-button onclick="modalDeleteTaxRate{{ $taxRate->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="taxRate" id="{{ $taxRate->id }}" deleting="tax rate" />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
