<?php

/*
 * Issue #127: saving a pipeline stage without a pipeline said "The pipeline
 * id field is required." - the column name, not the form's label.
 *
 * Issue #138: the create form's select showed the first pipeline as chosen,
 * but pipeline_id was null, so an untouched form failed that same rule.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\PipelineStageCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\PipelineStageEdit;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Services\PipelineStageService;

beforeEach(function () {
    $this->actingAsUserWithPermissions(['create crm pipelines']);
});

test('a pipeline stage without a pipeline names the field as the form does', function () {
    $component = Livewire::test(PipelineStageCreate::class)
        ->set('name', 'Qualified')
        ->call('save')
        ->assertHasErrors(['pipeline_id' => 'required']);

    expect($component->errors()->first('pipeline_id'))
        ->toBe('The pipeline field is required.')
        ->not->toContain('pipeline id');

    expect(PipelineStage::count())->toBe(0);
});

describe('with pipelines', function () {
    beforeEach(function () {
        $this->leadPipeline = Pipeline::create(['name' => 'Lead Pipeline', 'model' => Lead::class]);
        $this->dealPipeline = Pipeline::create(['name' => 'Deal Pipeline', 'model' => Deal::class]);
    });

    test('the create form defaults to the pipeline its select shows first', function () {
        Livewire::test(PipelineStageCreate::class)
            ->assertSet('pipeline_id', $this->leadPipeline->id);
    });

    test('an untouched pipeline select saves the stage under the first pipeline', function () {
        Livewire::test(PipelineStageCreate::class)
            ->set('name', 'Qualified')
            ->call('save')
            ->assertHasNoErrors();

        $stage = PipelineStage::where('name', 'Qualified')->sole();

        expect($stage->pipeline_id)->toBe($this->leadPipeline->id);
    });

    test('choosing the blank option still requires a pipeline', function () {
        $component = Livewire::test(PipelineStageCreate::class)
            ->set('name', 'Qualified')
            ->set('pipeline_id', '')
            ->call('save')
            ->assertHasErrors(['pipeline_id' => 'required']);

        expect($component->errors()->first('pipeline_id'))
            ->toBe('The pipeline field is required.');

        expect(PipelineStage::where('name', 'Qualified')->exists())->toBeFalse();
    });

    test('the edit form keeps the stage\'s own pipeline', function () {
        $this->actingAsUserWithPermissions(['edit crm pipelines']);

        $stage = $this->dealPipeline->pipelineStages()->create(['name' => 'Negotiation', 'order' => 1]);

        Livewire::test(PipelineStageEdit::class, ['pipelineStage' => $stage])
            ->assertSet('pipeline_id', $this->dealPipeline->id);
    });
});

test('creation appends after reordered stages', function () {
    $this->actingAsUserWithPermissions(['create crm pipelines', 'edit crm pipelines']);
    $pipeline = Pipeline::create(['name' => 'Sales', 'model' => Deal::class]);
    $first = $pipeline->pipelineStages()->create(['name' => 'Closed Won', 'order' => 10]);
    $second = $pipeline->pipelineStages()->create(['name' => 'Negotiation', 'order' => 20]);
    $service = app(PipelineStageService::class);
    $service->reorder($pipeline->id, [$second->id, $first->id], $service->snapshot(collect([$first, $second])));
    Livewire::test(PipelineStageCreate::class)->set('name', 'New')->call('save')->assertHasNoErrors();
    expect($pipeline->pipelineStages()->orderBy('order')->pluck('name')->all())->toBe(['Negotiation', 'Closed Won', 'New'])
        ->and(PipelineStage::where('name', 'New')->sole()->order)->toBe(3);
});

test('stage editing preserves position or appends when the pipeline changes', function () {
    $this->actingAsUserWithPermissions(['edit crm pipelines']);
    $source = Pipeline::create(['name' => 'Source', 'model' => Lead::class]);
    $destination = Pipeline::create(['name' => 'Destination', 'model' => Deal::class]);
    $stage = $source->pipelineStages()->create(['name' => 'Original', 'order' => 4]);
    $destination->pipelineStages()->create(['name' => 'Last', 'order' => 8]);
    Livewire::test(PipelineStageEdit::class, ['pipelineStage' => $stage])
        ->set('name', 'Renamed')->call('save')->assertHasNoErrors();
    expect($stage->fresh()->order)->toBe(4);
    Livewire::test(PipelineStageEdit::class, ['pipelineStage' => $stage->fresh()])
        ->set('pipeline_id', $destination->id)->call('save')->assertHasNoErrors();
    expect($stage->fresh()->order)->toBe(9)->and($stage->fresh()->pipeline_id)->toBe($destination->id);
});

test('create and edit reject nonexistent deleted or foreign team pipelines', function ($kind) {
    config(['laravel-crm.teams' => true]);
    $this->actingAsUserWithPermissions(['create crm pipelines', 'edit crm pipelines'], ['current_team_id' => 1]);
    $own = Pipeline::create(['name' => 'Own', 'model' => Deal::class]);
    $stage = $own->pipelineStages()->create(['name' => 'Original', 'order' => 1]);
    $invalid = Pipeline::create(['name' => 'Invalid', 'model' => Deal::class, 'team_id' => $kind === 'foreign' ? 2 : 1]);
    if ($kind === 'deleted') {
        $invalid->delete();
    }
    $id = $kind === 'missing' ? 999999 : $invalid->id;
    Livewire::test(PipelineStageCreate::class)->set('name', 'Invalid')->set('pipeline_id', $id)->call('save')->assertHasErrors(['pipeline_id']);
    Livewire::test(PipelineStageEdit::class, ['pipelineStage' => $stage])->set('pipeline_id', $id)->call('save')->assertHasErrors(['pipeline_id']);
    expect($stage->fresh()->pipeline_id)->toBe($own->id)->and(PipelineStage::count())->toBe(1);
})->with(['missing', 'deleted', 'foreign']);

test('legacy update also appends a reassigned stage', function () {
    $this->actingAsUserWithPermissions(['edit crm pipelines']);
    $source = Pipeline::create(['name' => 'Source', 'model' => Lead::class]);
    $destination = Pipeline::create(['name' => 'Destination', 'model' => Deal::class]);
    $stage = $source->pipelineStages()->create(['name' => 'Original', 'order' => 4]);
    $destination->pipelineStages()->create(['name' => 'Last', 'order' => 8]);
    $this->put(route('laravel-crm.pipeline-stages.update', $stage), [
        'name' => 'Moved', 'pipeline_id' => $destination->id,
    ])->assertRedirect(route('laravel-crm.pipeline-stages.show', $stage));
    expect($stage->fresh()->order)->toBe(9)->and($stage->fresh()->pipeline_id)->toBe($destination->id);
});
