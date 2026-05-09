<?php

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\LeadService;

test('service creates a lead with minimum data', function () {
    $lead = app(LeadService::class)->create(new Request([
        'title' => 'New lead', 'description' => 'A short description',
        'currency' => 'USD', 'amount' => 250.00,
        'user_owner_id' => null, 'pipeline_stage_id' => null,
    ]));

    expect($lead)->toBeInstanceOf(Lead::class);
    expect($lead->title)->toBe('New lead');
    expect($lead->description)->toBe('A short description');
    expect($lead->currency)->toBe('USD');
    expect((int) $lead->fresh()->amount)->toBe(25000);
    expect($lead->lead_status_id)->toBe(1);
});

test('service attaches person and organization', function () {
    $person = Person::create(['first_name' => 'Jane']);
    $org = Organization::create(['name' => 'Acme']);

    $lead = app(LeadService::class)->create(new Request([
        'title' => 'Linked', 'currency' => 'USD',
    ]), $person, $org);

    expect($lead->person_id)->toBe($person->id);
    expect($lead->organization_id)->toBe($org->id);
});

test('service syncs labels', function () {
    $label = Label::create(['name' => 'Hot', 'hex' => 'ff0000']);

    $lead = app(LeadService::class)->create(new Request([
        'title' => 'L', 'currency' => 'USD', 'labels' => [$label->id],
    ]));

    expect($lead->fresh()->labels)->toHaveCount(1);
});

test('service updates an existing lead', function () {
    $lead = Lead::create(['title' => 'Old', 'currency' => 'USD']);

    app(LeadService::class)->update(new Request([
        'title' => 'New', 'description' => 'updated', 'amount' => 99, 'currency' => 'AUD',
    ]), $lead);

    $fresh = $lead->fresh();

    expect($fresh->title)->toBe('New');
    expect($fresh->description)->toBe('updated');
    expect($fresh->currency)->toBe('AUD');
    expect((int) $fresh->amount)->toBe(9900);
});
