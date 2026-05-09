<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsTemplate;

test('sms campaign uses prefixed table name', function () {
    expect((new SmsCampaign)->getTable())->toBe('crm_sms_campaigns');
});

test('creating an sms campaign assigns uuid', function () {
    $campaign = SmsCampaign::create(['name' => 'Flash Sale', 'body' => '50% off today only!']);
    expect(Str::isUuid($campaign->external_id))->toBeTrue();
});

test('observer auto increments number starting from 1000', function () {
    $first = SmsCampaign::create(['name' => 'A', 'body' => 'A']);
    $second = SmsCampaign::create(['name' => 'B', 'body' => 'B']);

    expect($first->number)->toBe(1000);
    expect($second->number)->toBe(1001);
});

test('observer sets campaign id from number', function () {
    $campaign = SmsCampaign::create(['name' => 'ID Test', 'body' => 'Body']);
    expect($campaign->campaign_id)->toBe('SC'.$campaign->number);
});

test('sms campaign default status is draft', function () {
    $campaign = SmsCampaign::create(['name' => 'Draft', 'body' => 'Hello']);
    expect($campaign->fresh()->status)->toBe('draft');
});

test('is editable only when draft', function () {
    $campaign = SmsCampaign::create(['name' => 'C', 'body' => 'B', 'status' => 'draft']);
    expect($campaign->isEditable())->toBeTrue();

    $campaign->update(['status' => 'scheduled']);
    expect($campaign->fresh()->isEditable())->toBeFalse();
});

test('is cancellable only when scheduled', function () {
    $campaign = SmsCampaign::create(['name' => 'C', 'body' => 'B', 'status' => 'scheduled']);
    expect($campaign->isCancellable())->toBeTrue();

    $campaign->update(['status' => 'sent']);
    expect($campaign->fresh()->isCancellable())->toBeFalse();
});

test('click rate returns zero when no recipients', function () {
    $campaign = new SmsCampaign(['total_recipients' => 0, 'unique_clicks_count' => 0]);
    expect($campaign->clickRate())->toBe(0.0);
});

test('click rate is calculated correctly', function () {
    $campaign = new SmsCampaign(['total_recipients' => 200, 'unique_clicks_count' => 20]);
    expect($campaign->clickRate())->toBe(10.0);
});

test('unsubscribe rate is calculated correctly', function () {
    $campaign = new SmsCampaign(['total_recipients' => 400, 'unsubscribes_count' => 8]);
    expect($campaign->unsubscribeRate())->toBe(2.0);
});

test('delivery rate is calculated correctly', function () {
    $campaign = new SmsCampaign(['total_recipients' => 100, 'delivered_count' => 95]);
    expect($campaign->deliveryRate())->toBe(95.0);
});

test('delivery rate returns zero when no recipients', function () {
    $campaign = new SmsCampaign(['total_recipients' => 0, 'delivered_count' => 0]);
    expect($campaign->deliveryRate())->toBe(0.0);
});

test('sms campaign uses soft deletes', function () {
    $campaign = SmsCampaign::create(['name' => 'Bin', 'body' => 'B']);
    $campaign->delete();

    $this->assertSoftDeleted('crm_sms_campaigns', ['id' => $campaign->id]);
    expect(SmsCampaign::count())->toBe(0);
});

test('sms campaign belongs to sms template', function () {
    $template = SmsTemplate::create(['name' => 'Promo', 'body' => 'Save now!']);
    $campaign = SmsCampaign::create(['name' => 'With Template', 'body' => 'Save now!', 'sms_template_id' => $template->id]);

    expect($campaign->fresh()->template->is($template))->toBeTrue();
});

test('number continues after soft deleted record', function () {
    $first = SmsCampaign::create(['name' => 'First', 'body' => 'B']);
    $first->delete();

    $second = SmsCampaign::create(['name' => 'Second', 'body' => 'B']);
    expect($second->number)->toBe(1001);
});
