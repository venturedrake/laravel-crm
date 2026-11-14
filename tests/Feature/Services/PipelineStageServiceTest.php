<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\PipelineStageService;

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm pipelines', 'edit crm pipelines', 'create crm pipelines']);
    $this->pipeline = Pipeline::create(['name' => 'Sales', 'model' => Deal::class]);
    $this->stages = collect(['Closed Won', 'Closed Lost', 'Negotiation'])->map(fn ($name, $index) => $this->pipeline->pipelineStages()->create([
        'name' => $name, 'order' => $index + 1, 'description' => 'Keep me',
        'color' => '#05b3a9', 'pipeline_stage_probability_id' => 5,
    ])
    );
    $this->service = app(PipelineStageService::class);
    $this->snapshot = $this->service->snapshot($this->stages);
    $this->proposed = [$this->stages[2]->id, $this->stages[0]->id, $this->stages[1]->id];
});

test('reordering preserves stage metadata and every kind of record assignment', function () {
    $records = collect([Lead::class, Deal::class, Quote::class])->map(fn ($model) => $model::create([
        'title' => 'Assigned', 'pipeline_id' => $this->pipeline->id, 'pipeline_stage_id' => $this->stages[2]->id,
    ]));
    $before = $this->stages->map(fn ($stage) => $stage->only(['id', 'external_id', 'name', 'description', 'color', 'pipeline_stage_probability_id', 'pipeline_id']));
    $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot);

    expect($this->pipeline->pipelineStages()->orderBy('order')->pluck('id')->all())->toBe($this->proposed)
        ->and($this->pipeline->pipelineStages()->orderBy('order')->pluck('order')->all())->toBe([1, 2, 3]);
    foreach ($this->stages as $index => $stage) {
        expect($stage->fresh()->only(array_keys($before[$index])))->toBe($before[$index]);
    }
    foreach ($records as $record) {
        expect($record->fresh()->pipeline_stage_id)->toBe($this->stages[2]->id);
    }
});

test('malformed or incomplete sequences save nothing', function ($case) {
    $ids = $this->proposed;
    $foreign = Pipeline::create(['name' => 'Other', 'model' => Deal::class])->pipelineStages()->create(['name' => 'Other', 'order' => 1]);
    $deleted = $this->pipeline->pipelineStages()->create(['name' => 'Deleted', 'order' => 4]);
    $deleted->delete();
    $ids = match ($case) {
        'duplicate' => [$ids[0], $ids[0], $ids[1]],
        'missing' => array_slice($ids, 1),
        'foreign' => [$ids[0], $ids[1], $foreign->id],
        'deleted' => [$ids[0], $ids[1], $deleted->id],
        'string' => [$ids[0], $ids[1], 'oops'],
        'nested' => [$ids[0], $ids[1], []],
        'float' => [$ids[0], $ids[1], 1.5],
        'associative' => ['a' => $ids[0], 'b' => $ids[1], 'c' => $ids[2]],
    };
    expect(fn () => $this->service->reorder($this->pipeline->id, $ids, $this->snapshot))->toThrow(ValidationException::class);
    expect($this->stages->map(fn ($stage) => $stage->fresh()->order)->all())->toBe([1, 2, 3]);
})->with(['duplicate', 'missing', 'foreign', 'deleted', 'string', 'nested', 'float', 'associative']);

test('outdated membership or order refuses the whole submission', function ($change) {
    match ($change) {
        'create' => $this->service->create(['name' => 'New', 'pipeline_id' => $this->pipeline->id]),
        'delete' => $this->stages[0]->delete(),
        'move' => $this->service->update($this->stages[0], ['name' => 'Moved', 'pipeline_id' => Pipeline::create(['name' => 'Other', 'model' => Deal::class])->id]),
        'reorder' => $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot),
    };
    $current = $this->pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get();
    expect(fn () => $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot))->toThrow(ValidationException::class);
    expect($this->service->snapshot($this->pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get()))->toBe($this->service->snapshot($current));
})->with(['create', 'delete', 'move', 'reorder']);

test('gaps and duplicate positions are normalized and deleted stages are left alone', function () {
    $this->stages[0]->update(['order' => 20]);
    $this->stages[1]->update(['order' => 20]);
    $this->stages[2]->update(['order' => 100]);
    $deleted = $this->pipeline->pipelineStages()->create(['name' => 'Deleted', 'order' => 127]);
    $deleted->delete();
    $snapshot = $this->service->snapshot($this->pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get());
    $this->service->reorder($this->pipeline->id, array_map('strval', $this->proposed), $snapshot);
    expect($this->pipeline->pipelineStages()->orderBy('order')->pluck('order')->all())->toBe([1, 2, 3])
        ->and($deleted->fresh()->order)->toBe(127);
});

test('denial on a later stage authorizes the complete batch before writing', function () {
    $denied = $this->stages[1];
    Gate::before(fn ($user, $ability, $args) => $ability === 'update' && ($args[0] ?? null) instanceof PipelineStage && $args[0]->is($denied) ? false : null);
    expect(fn () => $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot))->toThrow(AuthorizationException::class);
    expect($this->stages->map(fn ($stage) => $stage->fresh()->order)->all())->toBe([1, 2, 3]);
});

test('an exception during persistence rolls back earlier stage writes', function () {
    $failId = $this->stages[0]->id;
    PipelineStage::updating(function ($stage) use ($failId) {
        if ($stage->id === $failId) {
            throw new RuntimeException('Simulated write failure');
        }
    });
    expect(fn () => $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot))->toThrow(RuntimeException::class, 'Simulated write failure');
    expect($this->stages->map(fn ($stage) => $stage->fresh()->order)->all())->toBe([1, 2, 3]);
});

test('all write operations enforce their permissions', function () {
    $this->actingAsUserWithPermissions(['view crm pipelines']);
    expect(fn () => $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->service->create(['name' => 'New', 'pipeline_id' => $this->pipeline->id]))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->service->update($this->stages[0], ['name' => 'Changed', 'pipeline_id' => $this->pipeline->id]))->toThrow(AuthorizationException::class);
});

test('team scoped lookups reject foreign pipelines and stages on all write paths', function () {
    config(['laravel-crm.teams' => true]);
    $this->actingAsUserWithPermissions(['view crm pipelines', 'edit crm pipelines', 'create crm pipelines'], ['current_team_id' => 1]);
    $this->pipeline->update(['team_id' => 2]);
    $this->stages->each->update(['team_id' => 2]);
    $own = Pipeline::create(['name' => 'Own', 'model' => Deal::class]);
    $ownStage = $own->pipelineStages()->create(['name' => 'Own', 'order' => 1]);
    expect(fn () => $this->service->reorder($this->pipeline->id, $this->proposed, $this->snapshot))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $this->service->create(['name' => 'New', 'pipeline_id' => $this->pipeline->id]))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->update($ownStage, ['name' => 'Moved', 'pipeline_id' => $this->pipeline->id]))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->update($this->stages[0], ['name' => 'Stolen', 'pipeline_id' => $own->id]))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $this->service->reorder($own->id, [$this->stages[0]->id], $this->service->snapshot(collect([$ownStage]))))->toThrow(ValidationException::class);
});

test('append capacity is guarded and reorder can recover space left by gaps', function () {
    $this->stages[2]->update(['order' => 127]);
    expect(fn () => $this->service->create(['name' => 'Full', 'pipeline_id' => $this->pipeline->id]))->toThrow(ValidationException::class);
    $other = Pipeline::create(['name' => 'Other', 'model' => Deal::class])->pipelineStages()->create(['name' => 'Other', 'order' => 1]);
    expect(fn () => $this->service->update($other, ['name' => 'Moved', 'pipeline_id' => $this->pipeline->id]))->toThrow(ValidationException::class);
    expect($other->fresh()->name)->toBe('Other');
    $this->service->reorder($this->pipeline->id, $this->proposed, $this->service->snapshot($this->pipeline->pipelineStages()->orderBy('order')->orderBy('id')->get()));
    expect($this->service->create(['name' => 'New', 'pipeline_id' => $this->pipeline->id])->order)->toBe(4);
});

test('oversized reorder is rejected even on a database that allows wider integers', function () {
    foreach (range(4, 128) as $order) {
        $this->pipeline->pipelineStages()->create(['name' => 'Stage '.$order, 'order' => $order]);
    }
    $stages = $this->pipeline->pipelineStages()->orderBy('order')->get();
    expect(fn () => $this->service->reorder($this->pipeline->id, $stages->pluck('id')->reverse()->values()->all(), $this->service->snapshot($stages)))->toThrow(ValidationException::class);
    expect($this->stages[0]->fresh()->order)->toBe(1);
});
