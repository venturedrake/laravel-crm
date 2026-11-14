<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Pipelines;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\PipelineStageService;

class PipelineShow extends Component
{
    use AuthorizesRequests;
    use Toast;

    public Pipeline $pipeline;

    #[Locked]
    public string $orderingSnapshot = '';

    public function reorderStages(array $ids): void
    {
        $pipeline = Pipeline::query()->findOrFail($this->pipeline->id);
        $this->authorize('view', $pipeline);
        $this->authorize('update', $pipeline);
        $this->resetValidation();

        try {
            app(PipelineStageService::class)->reorder($pipeline->id, $ids, $this->orderingSnapshot);
            $this->success(ucfirst(__('laravel-crm::lang.pipeline_stage_order_saved')));
        } catch (ValidationException $exception) {
            if (! isset($exception->errors()['stageOrderConflict'])) {
                throw $exception;
            }

            $this->warning(ucfirst(__('laravel-crm::lang.pipeline_stage_order_changed')));
        }
    }

    public function render()
    {
        // Model hydration alone does not reapply the current team's scope.
        $this->pipeline = Pipeline::query()->findOrFail($this->pipeline->id);
        $this->authorize('view', $this->pipeline);
        $stages = $this->pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get();
        $this->orderingSnapshot = app(PipelineStageService::class)->snapshot($stages);

        return view('laravel-crm::livewire.settings.pipelines.pipeline-show', [
            'stages' => $stages,
            'canReorder' => Gate::allows('update', $this->pipeline)
                && $stages->every(fn ($stage) => Gate::allows('update', $stage)),
        ]);
    }
}
