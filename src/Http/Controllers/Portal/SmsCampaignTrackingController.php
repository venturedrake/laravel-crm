<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use VentureDrake\LaravelCrm\Models\SmsCampaignClick;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrm\Support\CampaignClickLink;

class SmsCampaignTrackingController extends Controller
{
    public function click(string $token, Request $request)
    {
        abort_unless($request->hasValidSignature(), 403);

        $recipient = SmsCampaignRecipient::where('tracking_token', $token)->firstOrFail();
        $url = CampaignClickLink::destination($request->query('u'));
        abort_if($url === null, 404);

        $userAgent = (string) $request->userAgent();

        if (! $this->isBot($userAgent) && ! $this->isDuplicate($recipient->id, $request->ip(), $userAgent)) {
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
                'user_agent' => mb_substr($userAgent, 0, 500),
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

    private function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        $signatures = ['bot', 'crawler', 'spider', 'preview', 'fetcher', 'facebookexternalhit', 'slackbot', 'whatsapp', 'urlpreview', 'monitor', 'pingdom', 'curl', 'wget', 'headless'];

        $needle = strtolower($userAgent);
        foreach ($signatures as $signature) {
            if (str_contains($needle, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function isDuplicate(int $recipientId, ?string $ip, string $userAgent): bool
    {
        $key = 'sms-click:'.$recipientId.':'.sha1(($ip ?? '').'|'.$userAgent);

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addMinutes(10));

        return false;
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
}
