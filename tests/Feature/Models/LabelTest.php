<?php

use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;

test('label uses prefixed table', function () {
    expect((new Label)->getTable())->toBe('crm_labels');
});

test('label can be attached to a lead', function () {
    $lead = Lead::create(['title' => 'L']);
    $label = Label::create(['name' => 'Hot', 'hex' => 'ff0000']);

    $lead->labels()->attach($label->id);

    expect($lead->fresh()->labels)->toHaveCount(1);
    expect($lead->fresh()->labels->first()->name)->toBe('Hot');
});

test('lead labels can be synced', function () {
    $lead = Lead::create(['title' => 'L']);
    $a = Label::create(['name' => 'A', 'hex' => '000000']);
    $b = Label::create(['name' => 'B', 'hex' => 'ffffff']);

    $lead->labels()->sync([$a->id, $b->id]);
    expect($lead->fresh()->labels)->toHaveCount(2);

    $lead->labels()->sync([$b->id]);
    expect($lead->fresh()->labels)->toHaveCount(1);
    expect($lead->fresh()->labels->first()->name)->toBe('B');
});
