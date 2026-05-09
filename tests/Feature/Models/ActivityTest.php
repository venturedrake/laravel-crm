<?php

use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Lead;

test('activity uses prefixed table', function () {
    expect((new Activity)->getTable())->toBe('crm_activities');
});

test('activity can be created for a lead', function () {
    $lead = Lead::create(['title' => 'L']);

    $activity = Activity::create([
        'log_name' => 'leads', 'description' => 'Lead created',
        'event' => 'created', 'recordable_type' => Lead::class, 'recordable_id' => $lead->id,
    ]);

    expect($activity->log_name)->toBe('leads');
    expect($activity->event)->toBe('created');
});
