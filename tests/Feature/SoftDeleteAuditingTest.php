<?php

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;

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

test('models are audited when created', function () {
    $lead = Lead::create(['title' => 'Audit me']);

    expect(DB::table('audits')
        ->where('auditable_type', Lead::class)
        ->where('auditable_id', $lead->id)
        ->where('event', 'created')
        ->count())->toBe(1);
});

test('person is audited when created', function () {
    $person = Person::create(['first_name' => 'Audit']);

    expect(DB::table('audits')
        ->where('auditable_type', Person::class)
        ->where('auditable_id', $person->id)
        ->where('event', 'created')
        ->count())->toBe(1);
});
