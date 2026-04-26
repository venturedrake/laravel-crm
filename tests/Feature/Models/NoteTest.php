<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\TestCase;

class NoteTest extends TestCase
{
    public function test_note_polymorphic_relation_to_lead(): void
    {
        $lead = Lead::create(['title' => 'Lead 1']);

        $note = $lead->notes()->create(['content' => 'Important note']);

        $this->assertSame('Important note', $note->content);
        $this->assertSame($lead->id, $note->noteable->id);
    }

    public function test_note_uses_soft_deletes(): void
    {
        $lead = Lead::create(['title' => 'L']);
        $note = $lead->notes()->create(['content' => 'Bye']);

        $note->delete();

        $this->assertSoftDeleted('crm_notes', ['id' => $note->id]);
    }
}
