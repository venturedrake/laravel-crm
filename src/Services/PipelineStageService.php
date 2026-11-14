<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class PipelineStageService
{
    // The installed schema uses a signed tinyInteger. Positions start at one.
    public const MAX_ORDER = 127;

    public function snapshot(Collection $stages): string
    {
        return hash('sha256', $stages->map(fn ($stage) => [
            (int) $stage->id, (int) $stage->order,
        ])->values()->toJson());
    }

    public function reorder(int $pipelineId, array $ids, string $snapshot): void
    {
        DB::transaction(function () use ($pipelineId, $ids, $snapshot) {
            $pipeline = Pipeline::query()->lockForUpdate()->findOrFail($pipelineId);
            Gate::authorize('update', $pipeline);

            $stages = $pipeline->pipelineStages()->orderBy('order')->orderBy('id')->lockForUpdate()->get();
            foreach ($stages as $stage) {
                Gate::authorize('update', $stage);
            }

            if (! hash_equals($this->snapshot($stages), $snapshot)) {
                throw ValidationException::withMessages([
                    'stageOrderConflict' => __('laravel-crm::lang.pipeline_stage_order_changed'),
                ]);
            }

            if (! array_is_list($ids) || collect($ids)->contains(fn ($id) => ! (is_int($id) || (is_string($id) && ctype_digit($id))) || (int) $id < 1
            )) {
                $this->invalidSequence();
            }

            $ids = array_map('intval', $ids);
            if (count(array_unique($ids)) !== count($ids)
                || collect($ids)->sort()->values()->all() !== $stages->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                $this->invalidSequence();
            }

            $this->ensureCapacity(count($ids));
            $byId = $stages->keyBy('id');
            foreach ($ids as $index => $id) {
                $byId[$id]->update(['order' => $index + 1]);
            }
        });
    }

    public function create(array $data): PipelineStage
    {
        Gate::authorize('create', PipelineStage::class);

        return DB::transaction(function () use ($data) {
            $pipeline = $this->resolvePipeline($data['pipeline_id'] ?? null);

            return $pipeline->pipelineStages()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'order' => $this->appendPosition($pipeline),
            ]);
        });
    }

    public function update(PipelineStage $stage, array $data): PipelineStage
    {
        return DB::transaction(function () use ($stage, $data) {
            // Read the source afresh; a mounted model can be from an older request.
            $current = PipelineStage::query()->findOrFail($stage->id);
            Gate::authorize('update', $current);
            $destinationId = $data['pipeline_id'] ?? null;
            if (! is_scalar($destinationId) || ! ctype_digit((string) $destinationId)) {
                $this->invalidPipeline();
            }

            // All writers lock pipelines before stages, in ascending ID order.
            $pipelines = Pipeline::query()->whereIn('id', [$current->pipeline_id, $destinationId])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $destination = $pipelines->get($destinationId);
            if (! $destination || ! $pipelines->has($current->pipeline_id)) {
                $this->invalidPipeline();
            }

            $locked = PipelineStage::query()->lockForUpdate()->findOrFail($stage->id);
            Gate::authorize('update', $locked);
            if ((int) $locked->pipeline_id !== (int) $current->pipeline_id) {
                throw ValidationException::withMessages([
                    'pipeline_id' => __('laravel-crm::lang.pipeline_stage_pipeline_changed'),
                ]);
            }

            $attributes = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'pipeline_id' => $destination->id,
            ];
            if ((int) $locked->pipeline_id !== (int) $destination->id) {
                $attributes['order'] = $this->appendPosition($destination);
            }
            $locked->update($attributes);

            return $locked;
        });
    }

    protected function resolvePipeline($id): Pipeline
    {
        if (! is_scalar($id) || ! ctype_digit((string) $id)
            || ! $pipeline = Pipeline::query()->lockForUpdate()->find($id)) {
            $this->invalidPipeline();
        }

        return $pipeline;
    }

    protected function appendPosition(Pipeline $pipeline): int
    {
        $position = max(0, (int) $pipeline->pipelineStages()->lockForUpdate()->get(['id', 'order'])->max('order')) + 1;
        $this->ensureCapacity($position);

        return $position;
    }

    protected function ensureCapacity(int $position): void
    {
        if ($position > self::MAX_ORDER) {
            throw ValidationException::withMessages([
                'pipeline_id' => __('laravel-crm::lang.pipeline_stage_order_capacity'),
            ]);
        }
    }

    protected function invalidPipeline(): never
    {
        throw ValidationException::withMessages([
            'pipeline_id' => __('validation.exists', ['attribute' => __('laravel-crm::lang.pipeline')]),
        ]);
    }

    protected function invalidSequence(): never
    {
        throw ValidationException::withMessages([
            'stageOrder' => __('laravel-crm::lang.pipeline_stage_order_invalid'),
        ]);
    }
}
