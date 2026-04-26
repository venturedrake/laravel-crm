<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Services;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\LeadService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class LeadServiceTest extends TestCase
{
    private function service(): LeadService
    {
        return app(LeadService::class);
    }

    private function request(array $attributes): Request
    {
        return new Request($attributes);
    }

    public function test_service_creates_a_lead_with_minimum_data(): void
    {
        $lead = $this->service()->create($this->request([
            'title' => 'New lead',
            'description' => 'A short description',
            'currency' => 'USD',
            'amount' => 250.00,
            'user_owner_id' => null,
            'pipeline_stage_id' => null,
        ]));

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertSame('New lead', $lead->title);
        $this->assertSame('A short description', $lead->description);
        $this->assertSame('USD', $lead->currency);
        $this->assertSame(25000, (int) $lead->fresh()->amount);
        $this->assertSame(1, $lead->lead_status_id);
    }

    public function test_service_attaches_person_and_organization(): void
    {
        $person = Person::create(['first_name' => 'Jane']);
        $org = Organization::create(['name' => 'Acme']);

        $lead = $this->service()->create($this->request([
            'title' => 'Linked',
            'currency' => 'USD',
        ]), $person, $org);

        $this->assertSame($person->id, $lead->person_id);
        $this->assertSame($org->id, $lead->organization_id);
    }

    public function test_service_syncs_labels(): void
    {
        $label = Label::create(['name' => 'Hot', 'hex' => 'ff0000']);

        $lead = $this->service()->create($this->request([
            'title' => 'L',
            'currency' => 'USD',
            'labels' => [$label->id],
        ]));

        $this->assertCount(1, $lead->fresh()->labels);
    }

    public function test_service_updates_an_existing_lead(): void
    {
        $lead = Lead::create(['title' => 'Old', 'currency' => 'USD']);

        $this->service()->update($this->request([
            'title' => 'New',
            'description' => 'updated',
            'amount' => 99,
            'currency' => 'AUD',
        ]), $lead);

        $fresh = $lead->fresh();

        $this->assertSame('New', $fresh->title);
        $this->assertSame('updated', $fresh->description);
        $this->assertSame('AUD', $fresh->currency);
        $this->assertSame(9900, (int) $fresh->amount);
    }
}
