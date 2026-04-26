<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\TestCase;

class LeadTest extends TestCase
{
    public function test_lead_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_leads', (new Lead)->getTable());

        config()->set('laravel-crm.db_table_prefix', 'foo_');
        $this->assertSame('foo_leads', (new Lead)->getTable());
    }

    public function test_creating_a_lead_assigns_external_id_uuid(): void
    {
        $lead = Lead::create(['title' => 'New deal']);

        $this->assertTrue(Str::isUuid($lead->external_id));
    }

    public function test_creating_a_lead_auto_increments_number_starting_from_1000(): void
    {
        $first = Lead::create(['title' => 'A']);
        $second = Lead::create(['title' => 'B']);
        $third = Lead::create(['title' => 'C']);

        $this->assertSame(1000, $first->number);
        $this->assertSame(1001, $second->number);
        $this->assertSame(1002, $third->number);
    }

    public function test_lead_id_is_built_from_prefix_plus_number(): void
    {
        app('laravel-crm.settings')->set('lead_prefix', 'L');

        $lead = Lead::create(['title' => 'Prefixed']);

        $this->assertSame('L', $lead->prefix);
        $this->assertSame('L1000', $lead->lead_id);
    }

    public function test_set_amount_attribute_multiplies_by_one_hundred(): void
    {
        $lead = Lead::create(['title' => 'Money', 'amount' => 12.50]);

        $this->assertSame(1250, (int) $lead->fresh()->amount);
    }

    public function test_set_amount_attribute_handles_null(): void
    {
        $lead = Lead::create(['title' => 'No money', 'amount' => null]);

        $this->assertNull($lead->fresh()->amount);
    }

    public function test_lead_uses_soft_deletes(): void
    {
        $lead = Lead::create(['title' => 'Soft delete me']);
        $lead->delete();

        $this->assertSoftDeleted('crm_leads', ['id' => $lead->id]);
        $this->assertSame(1, Lead::withTrashed()->count());
        $this->assertSame(0, Lead::count());
    }

    public function test_lead_number_continues_after_soft_deleted_records(): void
    {
        $first = Lead::create(['title' => 'First']);
        $first->delete();

        $second = Lead::create(['title' => 'Second']);

        $this->assertSame(1001, $second->number);
    }

    public function test_lead_relationships_are_defined(): void
    {
        $lead = new Lead;

        $this->assertInstanceOf(BelongsTo::class, $lead->person());
        $this->assertInstanceOf(BelongsTo::class, $lead->organization());
        $this->assertInstanceOf(BelongsTo::class, $lead->customer());
        $this->assertInstanceOf(BelongsTo::class, $lead->leadSource());
        $this->assertInstanceOf(BelongsTo::class, $lead->ownerUser());
        $this->assertInstanceOf(BelongsTo::class, $lead->pipeline());
        $this->assertInstanceOf(BelongsTo::class, $lead->pipelineStage());
        $this->assertInstanceOf(MorphMany::class, $lead->emails());
        $this->assertInstanceOf(MorphMany::class, $lead->phones());
        $this->assertInstanceOf(MorphMany::class, $lead->addresses());
        $this->assertInstanceOf(MorphToMany::class, $lead->labels());
    }

    public function test_lead_is_auditable(): void
    {
        $lead = new Lead;
        $this->assertInstanceOf(Auditable::class, $lead);
    }
}
