<div class="crm-content">
    <x-mary-header title="Feature Statuses" progress-indicator>
        <x-slot:actions>
            @can('manage crm feature statuses')
                <x-mary-button label="Create Status" link="{{ url(route('laravel-crm.feature-statuses.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$featureStatuses" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_color', $status)
                <span class="inline-block w-5 h-5 rounded-full align-middle border" style="background-color: {{ $status->color ?? '#6c757d' }}"></span>
                <span class="ml-2">{{ $status->color }}</span>
            @endscope
            @scope('actions', $status)
                @can('manage crm feature statuses')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.feature-statuses.edit', $status)) }}" class="btn-sm btn-square btn-outline" />
                    <x-mary-button onclick="modalDeleteFeatureStatus{{ $status->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="featureStatus" id="{{ $status->id }}" />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
