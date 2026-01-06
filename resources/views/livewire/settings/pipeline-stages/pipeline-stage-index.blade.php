<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.pipeline_stages')) }}" progress-indicator></x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$pipelineStages" link="/pipeline-stages/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('actions', $pipelineStage)
                @can('view crm pipelines')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.pipeline-stages.show', $pipelineStage)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm pipelines')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.pipeline-stages.edit', $pipelineStage)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm pipelines')
                    <x-mary-button onclick="modalDeletePipelineStage{{ $pipelineStage->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="pipelineStage" id="{{ $pipelineStage->id }}" deleting="pipeline stage"  />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
