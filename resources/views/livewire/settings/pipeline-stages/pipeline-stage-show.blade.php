<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.pipeline_stage')) }}: {{ $pipelineStage->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_pipeline_stages')) }}" link="{{ url(route('laravel-crm.pipeline-stages.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm pipelines')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.pipeline-stages.edit', $pipelineStage)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm pipelines')
                <x-mary-button onclick="modalDeletePipelineStage{{ $pipelineStage->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="pipelineStage" id="{{ $pipelineStage->id }}" deleting="pipeline stage" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $pipelineStage->description }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.pipeline')) }}" shadow separator>
                <div class="grid gap-y-3">
                    {{ $pipelineStage->pipeline->name }}
                </div>
            </x-mary-card>
        </div>
    </div>
    
</div>
