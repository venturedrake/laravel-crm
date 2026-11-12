<?php

/*
 * Issue #127: saving a pipeline stage without a pipeline said "The pipeline
 * id field is required." - the column name, not the form's label.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\PipelineStageCreate;
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
