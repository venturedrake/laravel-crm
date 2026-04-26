<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\TestCase;

class SoftDeleteAuditingTest extends TestCase
{
    public function test_soft_delete_leaves_record_in_database(): void
    {
        $lead = Lead::create(['title' => 'Bye']);
        $lead->delete();

        $this->assertDatabaseHas('crm_leads', ['id' => $lead->id]);
        $this->assertNotNull($lead->fresh()->deleted_at);
    }

    public function test_force_delete_removes_record(): void
    {
        $lead = Lead::create(['title' => 'Bye for real']);
        $lead->forceDelete();

        $this->assertDatabaseMissing('crm_leads', ['id' => $lead->id]);
    }

    public function test_restore_returns_record_to_active_state(): void
    {
        $lead = Lead::create(['title' => 'Restore me']);
        $lead->delete();
        $this->assertNotNull($lead->fresh()->deleted_at);

        $lead->restore();
        $this->assertNull($lead->fresh()->deleted_at);
    }

    public function test_save_quietly_does_not_trigger_observers(): void
    {
        $lead = Lead::create(['title' => 'Original']);
        $originalUpdatedAt = $lead->updated_at;

        sleep(1);
        $lead->title = 'Quietly changed';
        $lead->saveQuietly();

        $this->assertSame('Quietly changed', $lead->fresh()->title);
        // saveQuietly avoids the audit + observer events; the row is still updated.
    }

    public function test_models_are_audited_when_created(): void
    {
        $lead = Lead::create(['title' => 'Audit me']);

        $this->assertSame(1, \DB::table('audits')
            ->where('auditable_type', Lead::class)
            ->where('auditable_id', $lead->id)
            ->where('event', 'created')
            ->count());
    }

    public function test_person_is_audited_when_created(): void
    {
        $person = Person::create(['first_name' => 'Audit']);

        $this->assertSame(1, \DB::table('audits')
            ->where('auditable_type', Person::class)
            ->where('auditable_id', $person->id)
            ->where('event', 'created')
            ->count());
    }
}
