<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.pipeline')) }}: {{ $pipeline->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_pipelines')) }}" link="{{ url(route('laravel-crm.pipelines.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm pipelines')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.pipelines.edit', $pipeline)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5 items-start">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.stages')) }}" shadow separator>
                @if($canReorder && $stages->count() > 1)
                    <p class="mb-3 text-sm">{{ ucfirst(__('laravel-crm::lang.pipeline_stage_order_help')) }}</p>
                @endif
                <div
                    x-data="{
                        busy: false,
                        failed: false,
                        originalRows: [],
                        init() {
                            this.initializeSortable();
                            this.$wire.$hook('request', ({ payload, fail }) => {
                                if (! payload.includes('reorderStages')) return;
                                fail(() => {
                                    this.failed = true;
                                    this.busy = false;
                                    if (this.$refs.stages) window.Sortable.get(this.$refs.stages)?.option('disabled', false);
                                });
                            });
                        },
                        initializeSortable() {
                            this.$nextTick(() => {
                                if (! this.$refs.stages?.isConnected || window.Sortable.get(this.$refs.stages) || ! @js($canReorder) || this.$refs.stages.children.length < 2) return;
                                window.Sortable.create(this.$refs.stages, {
                                    handle: '[data-stage-handle]',
                                    draggable: '[data-stage-id]',
                                    animation: 150,
                                    onStart: () => { this.originalRows = [...this.$refs.stages.children]; },
                                    onEnd: (event) => {
                                        const ids = this.ids();
                                        this.originalRows.forEach(row => this.$refs.stages.appendChild(row));
                                        if (event.oldIndex !== event.newIndex) this.save(ids);
                                    },
                                });
                            });
                        },
                        ids() { return [...this.$refs.stages.children].map(row => row.dataset.stageId); },
                        async save(ids) {
                            if (this.busy) return;
                            this.busy = true;
                            this.failed = false;
                            window.Sortable.get(this.$refs.stages)?.option('disabled', true);
                            try { await this.$wire.reorderStages(ids); }
                            catch (error) { this.failed = true; }
                            finally {
                                this.busy = false;
                                if (this.$refs.stages) window.Sortable.get(this.$refs.stages)?.option('disabled', false);
                            }
                        },
                        destroy() {
                            if (this.$refs.stages) window.Sortable.get(this.$refs.stages)?.destroy();
                        },
                    }"
                    x-on:livewire:navigating.document="destroy()"
                    x-on:livewire:navigated.document="initializeSortable()"
                    x-bind:aria-busy="busy"
                >
                    <div x-ref="stages" class="grid gap-y-3">
                        @foreach($stages as $stage)
                            <div wire:key="pipeline-stage-{{ $stage->id }}" data-stage-id="{{ $stage->id }}" class="flex items-center gap-3">
                                @if($canReorder && $stages->count() > 1)
                                    <span data-stage-handle class="cursor-grab" title="{{ ucfirst(__('laravel-crm::lang.drag_stage', ['stage' => $stage->name])) }}"><x-mary-icon name="fas.arrows-alt-v" /></span>
                                @endif
                                <span class="flex-1">{{ $stage->name }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p x-show="failed" x-cloak role="alert" class="text-error mt-3">{{ ucfirst(__('laravel-crm::lang.pipeline_stage_order_failed')) }}</p>
                    @error('stageOrder') <p role="alert" class="text-error mt-3">{{ $message }}</p> @enderror
                    @error('pipeline_id') <p role="alert" class="text-error mt-3">{{ $message }}</p> @enderror
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
