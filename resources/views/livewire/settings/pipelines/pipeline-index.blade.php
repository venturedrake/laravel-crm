<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.pipelines')) }}" progress-indicator></x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$pipelines" link="/pipelines/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('actions', $pipeline)
                @can('view crm pipelines')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.pipelines.show', $pipeline)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm pipelines')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.pipelines.edit', $pipeline)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
