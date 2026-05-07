<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Tests\TestCase;

class SmsCampaignTest extends TestCase
{
    public function test_sms_campaign_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_sms_campaigns', (new SmsCampaign)->getTable());
    }

    public function test_creating_an_sms_campaign_assigns_uuid(): void
    {
        $campaign = SmsCampaign::create([
            'name' => 'Flash Sale',
            'body' => '50% off today only!',
        ]);

        $this->assertTrue(Str::isUuid($campaign->external_id));
    }

    public function test_observer_auto_increments_number_starting_from_1000(): void
    {
        $first = SmsCampaign::create(['name' => 'A', 'body' => 'A']);
        $second = SmsCampaign::create(['name' => 'B', 'body' => 'B']);

        $this->assertSame(1000, $first->number);
        $this->assertSame(1001, $second->number);
    }

    public function test_observer_sets_campaign_id_from_number(): void
    {
        $campaign = SmsCampaign::create(['name' => 'ID Test', 'body' => 'Body']);

        $this->assertSame('SC'.$campaign->number, $campaign->campaign_id);
    }

    public function test_sms_campaign_default_status_is_draft(): void
    {
        $campaign = SmsCampaign::create(['name' => 'Draft', 'body' => 'Hello']);

        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_is_editable_only_when_draft(): void
    {
        $campaign = SmsCampaign::create(['name' => 'C', 'body' => 'B', 'status' => 'draft']);
        $this->assertTrue($campaign->isEditable());

        $campaign->update(['status' => 'scheduled']);
        $this->assertFalse($campaign->fresh()->isEditable());
    }

    public function test_is_cancellable_only_when_scheduled(): void
    {
        $campaign = SmsCampaign::create(['name' => 'C', 'body' => 'B', 'status' => 'scheduled']);
        $this->assertTrue($campaign->isCancellable());

        $campaign->update(['status' => 'sent']);
        $this->assertFalse($campaign->fresh()->isCancellable());
    }

    public function test_click_rate_returns_zero_when_no_recipients(): void
    {
        $campaign = new SmsCampaign(['total_recipients' => 0, 'unique_clicks_count' => 0]);
        $this->assertSame(0.0, $campaign->clickRate());
    }

    public function test_click_rate_is_calculated_correctly(): void
    {
        $campaign = new SmsCampaign(['total_recipients' => 200, 'unique_clicks_count' => 20]);
        $this->assertSame(10.0, $campaign->clickRate());
    }

    public function test_unsubscribe_rate_is_calculated_correctly(): void
    {
        $campaign = new SmsCampaign(['total_recipients' => 400, 'unsubscribes_count' => 8]);
        $this->assertSame(2.0, $campaign->unsubscribeRate());
    }

    public function test_delivery_rate_is_calculated_correctly(): void
    {
        $campaign = new SmsCampaign(['total_recipients' => 100, 'delivered_count' => 95]);
        $this->assertSame(95.0, $campaign->deliveryRate());
    }

    public function test_delivery_rate_returns_zero_when_no_recipients(): void
    {
        $campaign = new SmsCampaign(['total_recipients' => 0, 'delivered_count' => 0]);
        $this->assertSame(0.0, $campaign->deliveryRate());
    }

    public function test_sms_campaign_uses_soft_deletes(): void
    {
        $campaign = SmsCampaign::create(['name' => 'Bin', 'body' => 'B']);
        $campaign->delete();

        $this->assertSoftDeleted('crm_sms_campaigns', ['id' => $campaign->id]);
        $this->assertSame(0, SmsCampaign::count());
    }

    public function test_sms_campaign_belongs_to_sms_template(): void
    {
        $template = SmsTemplate::create(['name' => 'Promo', 'body' => 'Save now!']);

        $campaign = SmsCampaign::create([
            'name' => 'With Template',
            'body' => 'Save now!',
            'sms_template_id' => $template->id,
        ]);

        $this->assertTrue($campaign->fresh()->template->is($template));
    }

    public function test_number_continues_after_soft_deleted_record(): void
    {
        $first = SmsCampaign::create(['name' => 'First', 'body' => 'B']);
        $first->delete();

        $second = SmsCampaign::create(['name' => 'Second', 'body' => 'B']);
        $this->assertSame(1001, $second->number);
    }
}
