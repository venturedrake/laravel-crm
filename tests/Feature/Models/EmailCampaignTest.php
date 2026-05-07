<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Tests\TestCase;

class EmailCampaignTest extends TestCase
{
    public function test_email_campaign_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_email_campaigns', (new EmailCampaign)->getTable());
    }

    public function test_creating_an_email_campaign_assigns_uuid(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'Welcome Series',
            'subject' => 'Welcome!',
            'body' => '<p>Hello</p>',
        ]);

        $this->assertTrue(Str::isUuid($campaign->external_id));
    }

    public function test_observer_auto_increments_number_starting_from_1000(): void
    {
        $first = EmailCampaign::create(['name' => 'A', 'subject' => 'A', 'body' => 'A']);
        $second = EmailCampaign::create(['name' => 'B', 'subject' => 'B', 'body' => 'B']);

        $this->assertSame(1000, $first->number);
        $this->assertSame(1001, $second->number);
    }

    public function test_observer_sets_campaign_id_from_number(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'Test Campaign',
            'subject' => 'Test',
            'body' => 'Body',
        ]);

        $this->assertSame('EC'.$campaign->number, $campaign->campaign_id);
    }

    public function test_email_campaign_default_status_is_draft(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'Draft',
            'subject' => 'Draft subject',
            'body' => 'Body',
        ]);

        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_is_editable_only_when_draft(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'C', 'subject' => 'S', 'body' => 'B', 'status' => 'draft',
        ]);
        $this->assertTrue($campaign->isEditable());

        $campaign->update(['status' => 'scheduled']);
        $this->assertFalse($campaign->fresh()->isEditable());
    }

    public function test_is_cancellable_only_when_scheduled(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'C', 'subject' => 'S', 'body' => 'B', 'status' => 'scheduled',
        ]);
        $this->assertTrue($campaign->isCancellable());

        $campaign->update(['status' => 'sent']);
        $this->assertFalse($campaign->fresh()->isCancellable());
    }

    public function test_open_rate_returns_zero_when_no_recipients(): void
    {
        $campaign = new EmailCampaign(['total_recipients' => 0, 'unique_opens_count' => 0]);
        $this->assertSame(0.0, $campaign->openRate());
    }

    public function test_open_rate_is_calculated_correctly(): void
    {
        $campaign = new EmailCampaign(['total_recipients' => 100, 'unique_opens_count' => 25]);
        $this->assertSame(25.0, $campaign->openRate());
    }

    public function test_click_rate_is_calculated_correctly(): void
    {
        $campaign = new EmailCampaign(['total_recipients' => 200, 'unique_clicks_count' => 10]);
        $this->assertSame(5.0, $campaign->clickRate());
    }

    public function test_unsubscribe_rate_is_calculated_correctly(): void
    {
        $campaign = new EmailCampaign(['total_recipients' => 1000, 'unsubscribes_count' => 5]);
        $this->assertSame(0.5, $campaign->unsubscribeRate());
    }

    public function test_email_campaign_uses_soft_deletes(): void
    {
        $campaign = EmailCampaign::create(['name' => 'Bin', 'subject' => 'S', 'body' => 'B']);
        $campaign->delete();

        $this->assertSoftDeleted('crm_email_campaigns', ['id' => $campaign->id]);
        $this->assertSame(0, EmailCampaign::count());
        $this->assertSame(1, EmailCampaign::withTrashed()->count());
    }

    public function test_email_campaign_belongs_to_email_template(): void
    {
        $template = EmailTemplate::create([
            'name' => 'Template A',
            'subject' => 'Subject',
            'body' => '<p>Hello</p>',
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'With Template',
            'subject' => 'Subject',
            'body' => '<p>Hello</p>',
            'email_template_id' => $template->id,
        ]);

        $this->assertTrue($campaign->fresh()->template->is($template));
    }

    public function test_number_continues_after_soft_deleted_record(): void
    {
        $first = EmailCampaign::create(['name' => 'First', 'subject' => 'S', 'body' => 'B']);
        $first->delete();

        $second = EmailCampaign::create(['name' => 'Second', 'subject' => 'S', 'body' => 'B']);
        $this->assertSame(1001, $second->number);
    }
}
