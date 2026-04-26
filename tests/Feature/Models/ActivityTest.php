<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ActivityTest extends TestCase
{
    public function test_activity_uses_prefixed_table(): void
    {
        $this->assertSame('crm_activities', (new Activity)->getTable());
    }

    public function test_activity_can_be_created_for_a_lead(): void
    {
        $lead = Lead::create(['title' => 'L']);

        $activity = Activity::create([
            'log_name' => 'leads',
            'description' => 'Lead created',
            'event' => 'created',
            'recordable_type' => Lead::class,
            'recordable_id' => $lead->id,
        ]);

        $this->assertSame('leads', $activity->log_name);
        $this->assertSame('created', $activity->event);
    }
}
