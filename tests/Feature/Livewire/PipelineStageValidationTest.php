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
