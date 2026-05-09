<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailTemplate;

test('email campaign uses prefixed table name', function () {
    expect((new EmailCampaign)->getTable())->toBe('crm_email_campaigns');
});

test('creating an email campaign assigns uuid', function () {
    $campaign = EmailCampaign::create(['name' => 'Welcome Series', 'subject' => 'Welcome!', 'body' => '<p>Hello</p>']);

    expect(Str::isUuid($campaign->external_id))->toBeTrue();
});

test('observer auto increments number starting from 1000', function () {
    $first = EmailCampaign::create(['name' => 'A', 'subject' => 'A', 'body' => 'A']);
    $second = EmailCampaign::create(['name' => 'B', 'subject' => 'B', 'body' => 'B']);

    expect($first->number)->toBe(1000);
    expect($second->number)->toBe(1001);
});

test('observer sets campaign id from number', function () {
    $campaign = EmailCampaign::create(['name' => 'Test Campaign', 'subject' => 'Test', 'body' => 'Body']);

    expect($campaign->campaign_id)->toBe('EC'.$campaign->number);
});

test('email campaign default status is draft', function () {
    $campaign = EmailCampaign::create(['name' => 'Draft', 'subject' => 'Draft subject', 'body' => 'Body']);

    expect($campaign->fresh()->status)->toBe('draft');
});

test('is editable only when draft', function () {
    $campaign = EmailCampaign::create(['name' => 'C', 'subject' => 'S', 'body' => 'B', 'status' => 'draft']);
    expect($campaign->isEditable())->toBeTrue();

    $campaign->update(['status' => 'scheduled']);
    expect($campaign->fresh()->isEditable())->toBeFalse();
});

test('is cancellable only when scheduled', function () {
    $campaign = EmailCampaign::create(['name' => 'C', 'subject' => 'S', 'body' => 'B', 'status' => 'scheduled']);
    expect($campaign->isCancellable())->toBeTrue();

    $campaign->update(['status' => 'sent']);
    expect($campaign->fresh()->isCancellable())->toBeFalse();
});

test('open rate returns zero when no recipients', function () {
    $campaign = new EmailCampaign(['total_recipients' => 0, 'unique_opens_count' => 0]);
    expect($campaign->openRate())->toBe(0.0);
});

test('open rate is calculated correctly', function () {
    $campaign = new EmailCampaign(['total_recipients' => 100, 'unique_opens_count' => 25]);
    expect($campaign->openRate())->toBe(25.0);
});

test('click rate is calculated correctly', function () {
    $campaign = new EmailCampaign(['total_recipients' => 200, 'unique_clicks_count' => 10]);
    expect($campaign->clickRate())->toBe(5.0);
});

test('unsubscribe rate is calculated correctly', function () {
    $campaign = new EmailCampaign(['total_recipients' => 1000, 'unsubscribes_count' => 5]);
    expect($campaign->unsubscribeRate())->toBe(0.5);
});

test('email campaign uses soft deletes', function () {
    $campaign = EmailCampaign::create(['name' => 'Bin', 'subject' => 'S', 'body' => 'B']);
    $campaign->delete();

    $this->assertSoftDeleted('crm_email_campaigns', ['id' => $campaign->id]);
    expect(EmailCampaign::count())->toBe(0);
    expect(EmailCampaign::withTrashed()->count())->toBe(1);
});

test('email campaign belongs to email template', function () {
    $template = EmailTemplate::create(['name' => 'Template A', 'subject' => 'Subject', 'body' => '<p>Hello</p>']);
    $campaign = EmailCampaign::create([
        'name' => 'With Template', 'subject' => 'Subject', 'body' => '<p>Hello</p>',
        'email_template_id' => $template->id,
    ]);

    expect($campaign->fresh()->template->is($template))->toBeTrue();
});

test('number continues after soft deleted record', function () {
    $first = EmailCampaign::create(['name' => 'First', 'subject' => 'S', 'body' => 'B']);
    $first->delete();

    $second = EmailCampaign::create(['name' => 'Second', 'subject' => 'S', 'body' => 'B']);
    expect($second->number)->toBe(1001);
});
