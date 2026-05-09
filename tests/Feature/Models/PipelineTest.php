<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;

test('pipeline uses prefixed table', function () {
    expect((new Pipeline)->getTable())->toBe('crm_pipelines');
});

test('pipeline can be created with stages', function () {
    $pipeline = Pipeline::create(['name' => 'Sales', 'model' => Lead::class]);

    expect(Str::isUuid($pipeline->external_id))->toBeTrue();

    $stage = PipelineStage::create(['name' => 'Qualified', 'pipeline_id' => $pipeline->id]);

    expect($stage->pipeline_id)->toBe($pipeline->id);
});
