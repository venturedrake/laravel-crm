<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\EmailCampaignClick;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Support\CampaignClickLink;

class EmailCampaignTrackingController extends Controller
{
    public function open(string $token): Response
    {
        $recipient = EmailCampaignRecipient::where('tracking_token', $token)->first();

        if ($recipient) {
            $isFirstOpen = $recipient->first_opened_at === null;

            $recipient->increment('opens_count');
            $recipient->updateQuietly([
                'first_opened_at' => $recipient->first_opened_at ?? now(),
                'last_opened_at' => now(),
            ]);

            $campaign = $recipient->campaign;

            if ($campaign) {
                $campaign->increment('opens_count');

                if ($isFirstOpen) {
                    $campaign->increment('unique_opens_count');
                }
            }
        }

        return response($this->transparentGif(), 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($this->transparentGif()),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(string $token, Request $request)
    {
        abort_unless($request->hasValidSignature(), 403);

        $recipient = EmailCampaignRecipient::where('tracking_token', $token)->firstOrFail();
        $url = CampaignClickLink::destination($request->query('u'));
        abort_if($url === null, 404);

        $isFirstClick = $recipient->first_clicked_at === null;

        $recipient->increment('clicks_count');
        $recipient->updateQuietly([
            'first_clicked_at' => $recipient->first_clicked_at ?? now(),
            'last_clicked_at' => now(),
        ]);

        EmailCampaignClick::create([
            'email_campaign_recipient_id' => $recipient->id,
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

        return redirect()->away($url);
    }

    public function unsubscribeForm(string $token)
    {
        $recipient = EmailCampaignRecipient::where('tracking_token', $token)->first();

        if (! $recipient) {
            abort(404);
        }

        return view('laravel-crm::portal.email.unsubscribe', [
            'recipient' => $recipient,
            'token' => $token,
        ]);
    }

    public function unsubscribe(string $token)
    {
        $recipient = EmailCampaignRecipient::where('tracking_token', $token)->first();

        if (! $recipient) {
            abort(404);
        }

        if ($recipient->email) {
            $recipient->email->markUnsubscribed();
        }

        if ($recipient->unsubscribed_at === null) {
            $recipient->update(['unsubscribed_at' => now()]);

            if ($campaign = $recipient->campaign) {
                $campaign->increment('unsubscribes_count');
            }
        }

        return view('laravel-crm::portal.email.unsubscribed', [
            'recipient' => $recipient,
        ]);
    }

    private function transparentGif(): string
    {
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
}
