<?php

use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignClick;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignClick;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

function campaignClickRecipient(string $channel, string $destination): EmailCampaignRecipient|SmsCampaignRecipient
{
    if ($channel === 'email') {
        $campaign = EmailCampaign::create([
            'name' => 'Newsletter',
            'subject' => 'Offers',
            'body' => '<a href="'.e($destination).'">View offer</a>',
        ]);

        return $campaign->recipients()->create(['address' => 'reader@example.test']);
    }

    $campaign = SmsCampaign::create(['name' => 'Offers', 'body' => $destination]);

    return $campaign->recipients()->create([]);
}

function renderedCampaignClickUrl(EmailCampaignRecipient|SmsCampaignRecipient $recipient): string
{
    if ($recipient instanceof EmailCampaignRecipient) {
        $html = (new EmailCampaignMessage($recipient))->render();
        expect($html)->toContain('&amp;signature=');
        $document = new DOMDocument;
        $document->loadHTML($html);

        return $document->getElementsByTagName('a')->item(0)->getAttribute('href');
    }

    return strtok(SmsCampaignMessage::renderBody($recipient), "\n");
}

function assertNoCampaignClicks(EmailCampaign|SmsCampaign $campaign): void
{
    $campaign->refresh();
    expect($campaign->clicks_count)->toBe(0)
        ->and($campaign->unique_clicks_count)->toBe(0);
    $clicks = $campaign instanceof EmailCampaign ? EmailCampaignClick::count() : SmsCampaignClick::count();
    expect($clicks)->toBe(0);

    foreach ($campaign->recipients as $recipient) {
        expect($recipient->clicks_count)->toBe(0)
            ->and($recipient->first_clicked_at)->toBeNull()
            ->and($recipient->last_clicked_at)->toBeNull();
    }
}

it('redirects guests to the rendered destination and counts repeat clicks', function (string $channel) {
    $destination = 'https://shop.example.test/offers?item=42&sort=recent#details';
    $recipient = campaignClickRecipient($channel, $destination);
    $url = renderedCampaignClickUrl($recipient);
    $this->travel(1)->years();

    $this->get($url, ['User-Agent' => 'Mobile Safari'])->assertRedirect($destination);
    $recipient->refresh();
    $firstClick = $recipient->first_clicked_at;
    expect($firstClick)->not->toBeNull()
        ->and($recipient->last_clicked_at->equalTo($firstClick))->toBeTrue();

    if ($channel === 'sms') {
        $this->travel(1)->minute();
        foreach (['Mobile Safari', 'Slackbot'] as $agent) {
            $this->get($url, ['User-Agent' => $agent])->assertRedirect($destination);
        }
        $recipient->refresh();
        expect($recipient->clicks_count)->toBe(1)
            ->and($recipient->last_clicked_at->equalTo($firstClick))->toBeTrue();
    }

    $this->travel(11)->minutes();
    $this->get($url, ['User-Agent' => 'Mobile Safari'])->assertRedirect($destination);
    $recipient->refresh();
    expect($recipient->clicks()->pluck('original_url')->all())->toBe([$destination, $destination])
        ->and($recipient->clicks_count)->toBe(2)
        ->and($recipient->campaign->clicks_count)->toBe(2)
        ->and($recipient->campaign->unique_clicks_count)->toBe(1)
        ->and($recipient->first_clicked_at->equalTo($firstClick))->toBeTrue()
        ->and($recipient->last_clicked_at->greaterThan($firstClick))->toBeTrue();
})->with(['email', 'sms']);

it('rejects unsigned and altered click links before recording analytics', function (string $channel) {
    $destination = 'http://example.test/offers';
    $recipient = campaignClickRecipient($channel, $destination);
    $other = $recipient->campaign->recipients()->create([]);
    $url = renderedCampaignClickUrl($recipient);
    $unsigned = explode('&signature=', $url)[0];

    foreach ([
        $unsigned,
        $unsigned.'&signature=invalid',
        preg_replace('/([?&]u=)[^&]+/', '${1}'.base64_encode('https://example.test/other'), $url),
        str_replace($recipient->tracking_token, $other->tracking_token, $url),
    ] as $invalid) {
        $this->get($invalid, ['User-Agent' => 'Mobile Safari'])->assertForbidden()->assertHeaderMissing('Location');
    }

    assertNoCampaignClicks($recipient->campaign);
})->with(['email', 'sms']);

it('rejects a signed click link once its recipient is deleted', function (string $channel) {
    $recipient = campaignClickRecipient($channel, 'https://example.test/offers');
    $campaign = $recipient->campaign;
    $url = renderedCampaignClickUrl($recipient);
    $recipient->delete();

    $this->get($url, ['User-Agent' => 'Mobile Safari'])->assertNotFound()->assertHeaderMissing('Location');
    assertNoCampaignClicks($campaign);
})->with(['email', 'sms']);

it('rejects a signed click link with a malformed destination', function (string $channel, mixed $encoded) {
    $recipient = campaignClickRecipient($channel, 'https://example.test/offers');
    $url = URL::signedRoute("laravel-crm.{$channel}-tracking.click", [
        'token' => $recipient->tracking_token,
        'u' => $encoded,
    ]);

    $this->get($url, ['User-Agent' => 'Mobile Safari'])->assertNotFound()->assertHeaderMissing('Location');
    assertNoCampaignClicks($recipient->campaign);
})->with(['email', 'sms'])->with([
    'missing' => [null],
    'array' => [['bad']],
    'invalid encoding' => ['%'],
    'relative URL' => [rtrim(base64_encode('/offers'), '=')],
    'unsupported scheme' => [rtrim(base64_encode('ftp://files.example.test/offers'), '=')],
]);

it('counts the signed SMS link at a multipart segment boundary', function () {
    $destination = 'https://example.test/offers';
    $recipient = campaignClickRecipient('sms', $destination);
    $length = strlen(SmsCampaignMessage::renderBody($recipient));
    $segments = (int) ceil($length / 153);
    $body = str_repeat('x', $segments * 153 - $length - 1).' '.$destination;

    $recipient->campaign->update(['body' => $body]);
    expect(strlen(SmsCampaignMessage::renderBody($recipient)))->toBe($segments * 153)
        ->and(SmsCampaignMessage::segmentCount($body))->toBe($segments)
        ->and(SmsCampaignMessage::segmentCount('x'.$body))->toBe($segments + 1);
});
