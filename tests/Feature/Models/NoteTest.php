<?php

use VentureDrake\LaravelCrm\Models\Lead;

test('note polymorphic relation to lead', function () {
    $lead = Lead::create(['title' => 'Lead 1']);
    $note = $lead->notes()->create(['content' => 'Important note']);

    expect($note->content)->toBe('Important note');
    expect($note->noteable->id)->toBe($lead->id);
});

test('note uses soft deletes', function () {
    $lead = Lead::create(['title' => 'L']);
    $note = $lead->notes()->create(['content' => 'Bye']);
    $note->delete();

    $this->assertSoftDeleted('crm_notes', ['id' => $note->id]);
});
