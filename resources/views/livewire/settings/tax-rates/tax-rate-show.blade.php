<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.tax_rate')) }}:  {{ $taxRate->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_tax_rates')) }}" link="{{ url(route('laravel-crm.tax-rates.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm tax rates')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.tax-rates.edit', $taxRate)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm tax rates')
                <x-mary-button onclick="modalDeleteTaxRate{{ $taxRate->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="taxRate" id="{{ $taxRate->id }}" deleting="tax rate" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.name')) }}</strong>
                        <span>
                        {{ $taxRate->rate }}%
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $taxRate->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.default')) }}</strong>
                        <span>
                        {{ $taxRate->default ? 'YES' : 'NO'}}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.tax_type')) }}</strong>
                        <span>
                        {{ $taxRate->tax_type }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.products')) }} ({{ $taxRate->products()->count() }})" shadow separator>
                <div class="overflow-x-auto">
                    <table class="table">
                        <!-- head -->
                        <thead>
                        <tr>
                            <th class="px-0 pt-0">Name</th>
                            <th class="px-0 pt-0 text-center">Active</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($taxRate->products as $product)
                            <tr>
                                <td class="px-0">{{ $product->name }}</td>
                                <td class="text-center">{{ $product->active ? 'YES' : 'NO' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
