<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

/*
 * Campaign placeholders are `{key}`, as advertised by the copy buttons in the
 * editor. The `{{key}}` form (which older sample data used) must also render
 * cleanly: the single-brace regex used to consume only the inner pair, so the
 * preview showed "Hello {Jane},".
 */

it('substitutes single-brace placeholders in the preview body', function () {
    $html = EmailCampaignMessage::renderPreview('<p>Hello {first_name},</p>');

    expect($html)->toContain('Hello Jane,')->not->toContain('{Jane}');
});

it('substitutes double-brace placeholders whole in the preview body', function () {
    $html = EmailCampaignMessage::renderPreview('<p>Hello {{first_name}},</p><p>{{ last_name }}</p>');

    expect($html)
        ->toContain('Hello Jane,')
        ->toContain('<p>Smith</p>')
        ->not->toContain('{Jane}')
        ->not->toContain('{Smith}');
});

it('leaves unknown placeholders verbatim', function () {
    expect(EmailCampaignMessage::substitutePlaceholders('A {foo} and {{bar}}', ['first_name' => 'Jane']))
        ->toBe('A {foo} and {{bar}}');
});

it('html-escapes substituted values unless told not to', function () {
    $data = ['first_name' => 'Tom & <Jerry>'];

    expect(EmailCampaignMessage::substitutePlaceholders('{first_name}', $data))->toBe('Tom &amp; &lt;Jerry&gt;')
        ->and(EmailCampaignMessage::substitutePlaceholders('{first_name}', $data, false))->toBe('Tom & <Jerry>');
});

it('substitutes the preview text in the preview', function () {
    $html = EmailCampaignMessage::renderPreview('<p>Body</p>', 'A note for {{first_name}}');

    expect($html)->toContain('A note for Jane')->not->toContain('{Jane}');
});

it('renders the preview subject without braces', function () {
    expect(EmailCampaignMessage::renderPreviewSubject('Update from {{company_name}}'))
        ->toBe('Update from '.config('app.name'))
        ->and(EmailCampaignMessage::renderPreviewSubject('Hi {first_name}'))
        ->toBe('Hi Jane');
});

it('substitutes the subject, preview text and body when the campaign is sent', function () {
    $person = Person::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $campaign = EmailCampaign::create([
        'name' => 'Placeholder campaign',
        'subject' => 'Hi {first_name}, news from {{company_name}}',
        'preview_text' => 'Just for {{first_name}}',
        'body' => '<p>Hello {{first_name}} {last_name},</p>',
        'status' => 'draft',
    ]);

    $recipient = EmailCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'email_campaign_id' => $campaign->id,
        'person_id' => $person->id,
        'address' => 'ada@example.com',
        'tracking_token' => Str::random(32),
        'status' => 'pending',
    ]);

    $mailable = new EmailCampaignMessage($recipient);
    $html = $mailable->render();

    expect($mailable->subject)->toBe('Hi Ada, news from '.config('app.name'))
        ->and($html)->toContain('Hello Ada Lovelace,')
        ->and($html)->toContain('Just for Ada')
        ->and($html)->not->toContain('{Ada}')
        ->and($html)->not->toContain('{first_name}');
});

it('substitutes double-brace placeholders in the sms preview', function () {
    expect(SmsCampaignMessage::renderPreview('Hi {{first_name}}'))->toStartWith('Hi Jane')
        ->and(SmsCampaignMessage::renderPreview('Hi {first_name}'))->toStartWith('Hi Jane');
});
