<?php

use VentureDrake\LaravelCrm\Models\Lead;

test('soft delete leaves record in database', function () {
    $lead = Lead::create(['title' => 'Bye']);
    $lead->delete();

    $this->assertDatabaseHas('crm_leads', ['id' => $lead->id]);
    expect($lead->fresh()->deleted_at)->not->toBeNull();
});

test('force delete removes record', function () {
    $lead = Lead::create(['title' => 'Bye for real']);
    $lead->forceDelete();

    $this->assertDatabaseMissing('crm_leads', ['id' => $lead->id]);
});

test('restore returns record to active state', function () {
    $lead = Lead::create(['title' => 'Restore me']);
    $lead->delete();

    expect($lead->fresh()->deleted_at)->not->toBeNull();

    $lead->restore();

    expect($lead->fresh()->deleted_at)->toBeNull();
});

test('save quietly does not trigger observers', function () {
    $lead = Lead::create(['title' => 'Original']);

    sleep(1);
    $lead->title = 'Quietly changed';
    $lead->saveQuietly();

    expect($lead->fresh()->title)->toBe('Quietly changed');
});
