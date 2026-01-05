<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.pipeline')) }}: {{ $pipeline->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_pipelines')) }}" link="{{ url(route('laravel-crm.pipelines.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm pipelines')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.pipelines.edit', $pipeline)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.stages')) }}" shadow separator>
                <div class="grid gap-y-3">
                    @foreach($pipeline->pipelineStages as $stage)
                        <p>{{ $stage->name }}</p>
                    @endforeach
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.attached_to')) }}" shadow separator>
                <div class="grid gap-y-3">
                    {{ ucwords(\Illuminate\Support\Str::snake(class_basename($pipeline->model), ' ')) }}
                </div>
            </x-mary-card>
        </div>
    </div>
    
</div>
