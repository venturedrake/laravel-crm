<?php

use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

test('single brace placeholders are replaced', function () {
    expect(EmailCampaignMessage::substitutePlaceholders('Hello {first_name},', ['first_name' => 'Jane']))
        ->toBe('Hello Jane,');
});

test('double brace placeholders are replaced without leaving braces', function () {
    expect(EmailCampaignMessage::substitutePlaceholders('Hello {{first_name}}, from {{ company_name }}', ['first_name' => 'Jane', 'company_name' => 'Acme']))
        ->toBe('Hello Jane, from Acme');
});

test('unknown placeholders are left untouched', function () {
    expect(EmailCampaignMessage::substitutePlaceholders('{{product_name}} and {discount}', ['first_name' => 'Jane']))
        ->toBe('{{product_name}} and {discount}');
});

test('values are escaped for html but not for the subject', function () {
    $data = ['first_name' => 'Tom & Jerry'];

    expect(EmailCampaignMessage::substitutePlaceholders('{first_name}', $data))->toBe('Tom &amp; Jerry');
    expect(EmailCampaignMessage::substitutePlaceholders('{first_name}', $data, false))->toBe('Tom & Jerry');
});

test('seeded monthly newsletter preview renders the sample name', function () {
    $html = EmailCampaignMessage::renderPreview('<p>Hello {{first_name}},</p>');

    expect($html)->toContain('Hello Jane,')->not->toContain('{Jane}');
});

test('sms preview accepts double brace placeholders', function () {
    expect(SmsCampaignMessage::renderPreview('Hi {{first_name}}, see you soon.'))
        ->toStartWith('Hi Jane, see you soon.');
});
