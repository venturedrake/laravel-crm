<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\SmsCampaignClick;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;

class SmsCampaignTrackingController extends Controller
{
    public function click(string $token, Request $request)
    {
        $recipient = SmsCampaignRecipient::where('tracking_token', $token)->first();

        $encoded = $request->query('u');
        $url = $encoded ? $this->base64UrlDecode($encoded) : null;

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect('/');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return redirect('/');
        }

        if ($recipient) {
            $isFirstClick = $recipient->first_clicked_at === null;

            $recipient->increment('clicks_count');
            $recipient->updateQuietly([
                'first_clicked_at' => $recipient->first_clicked_at ?? now(),
                'last_clicked_at' => now(),
            ]);

            SmsCampaignClick::create([
                'sms_campaign_recipient_id' => $recipient->id,
                'original_url' => $url,
                'clicked_at' => now(),
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            $campaign = $recipient->campaign;

            if ($campaign) {
                $campaign->increment('clicks_count');

                if ($isFirstClick) {
                    $campaign->increment('unique_clicks_count');
                }
            }
        }

        return redirect()->away($url);
    }

    public function unsubscribeForm(string $token)
    {
        $recipient = SmsCampaignRecipient::where('tracking_token', $token)->first();

        if (! $recipient) {
            abort(404);
        }

        return view('laravel-crm::portal.sms.unsubscribe', [
            'recipient' => $recipient,
            'token' => $token,
        ]);
    }

    public function unsubscribe(string $token)
    {
        $recipient = SmsCampaignRecipient::where('tracking_token', $token)->first();

        if (! $recipient) {
            abort(404);
        }

        if ($recipient->phone) {
            $recipient->phone->markUnsubscribed();
        }

        if ($recipient->unsubscribed_at === null) {
            $recipient->update(['unsubscribed_at' => now()]);

            if ($campaign = $recipient->campaign) {
                $campaign->increment('unsubscribes_count');
            }
        }

        return view('laravel-crm::portal.sms.unsubscribed', [
            'recipient' => $recipient,
        ]);
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
