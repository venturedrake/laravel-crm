<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Tests\TestCase;

class LabelTest extends TestCase
{
    public function test_label_uses_prefixed_table(): void
    {
        $this->assertSame('crm_labels', (new Label)->getTable());
    }

    public function test_label_can_be_attached_to_a_lead(): void
    {
        $lead = Lead::create(['title' => 'L']);
        $label = Label::create(['name' => 'Hot', 'hex' => 'ff0000']);

        $lead->labels()->attach($label->id);

        $this->assertCount(1, $lead->fresh()->labels);
        $this->assertSame('Hot', $lead->fresh()->labels->first()->name);
    }

    public function test_lead_labels_can_be_synced(): void
    {
        $lead = Lead::create(['title' => 'L']);
        $a = Label::create(['name' => 'A', 'hex' => '000000']);
        $b = Label::create(['name' => 'B', 'hex' => 'ffffff']);

        $lead->labels()->sync([$a->id, $b->id]);
        $this->assertCount(2, $lead->fresh()->labels);

        $lead->labels()->sync([$b->id]);
        $this->assertCount(1, $lead->fresh()->labels);
        $this->assertSame('B', $lead->fresh()->labels->first()->name);
    }
}
