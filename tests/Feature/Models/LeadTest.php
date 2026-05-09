<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;

test('lead uses prefixed table name', function () {
    expect((new Lead)->getTable())->toBe('crm_leads');
    config()->set('laravel-crm.db_table_prefix', 'foo_');
    expect((new Lead)->getTable())->toBe('foo_leads');
});

test('creating a lead assigns external id uuid', function () {
    $lead = Lead::create(['title' => 'New Lead']);
    expect(Str::isUuid($lead->external_id))->toBeTrue();
});

test('creating a lead auto increments number starting from 1000', function () {
    $first = Lead::create(['title' => 'A']);
    $second = Lead::create(['title' => 'B']);

    expect($first->number)->toBe(1000);
    expect($second->number)->toBe(1001);
});

test('lead id is built from prefix plus number', function () {
    app('laravel-crm.settings')->set('lead_prefix', 'L');

    $lead = Lead::create(['title' => 'Prefixed']);

    expect($lead->prefix)->toBe('L');
    expect($lead->lead_id)->toBe('L1000');
});

test('set amount attribute multiplies by one hundred', function () {
    $lead = Lead::create(['title' => 'Money', 'amount' => 99.99]);

    expect((int) $lead->fresh()->amount)->toBe(9999);
});

test('amount attribute handles null', function () {
    $lead = Lead::create(['title' => 'Empty', 'amount' => null]);

    expect($lead->fresh()->amount)->toBeNull();
});

test('lead uses soft deletes', function () {
    $lead = Lead::create(['title' => 'Soft delete']);
    $lead->delete();

    $this->assertSoftDeleted('crm_leads', ['id' => $lead->id]);
});

test('lead has pipeline relationship', function () {
    $pipeline = Pipeline::create(['name' => 'Sales', 'model' => Lead::class]);
    $stage = PipelineStage::create(['name' => 'New', 'pipeline_id' => $pipeline->id]);
    $lead = Lead::create(['title' => 'Pipeline Lead', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id]);

    expect($lead->pipeline->is($pipeline))->toBeTrue();
    expect($lead->pipelineStage->is($stage))->toBeTrue();
});

test('lead belongs to person and organization', function () {
    $org = Organization::create(['name' => 'Acme']);
    $lead = Lead::create(['title' => 'Org Lead', 'organization_id' => $org->id]);

    expect($lead->organization->is($org))->toBeTrue();
});
