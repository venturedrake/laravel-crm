<?php

namespace VentureDrake\LaravelCrm\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;

class EmailCampaignMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public EmailCampaignRecipient $recipient;

    public function __construct(EmailCampaignRecipient $recipient)
    {
        $this->recipient = $recipient;
    }

    public static function availablePlaceholders(): array
    {
        return [
            'first_name' => 'Recipient first name',
            'last_name' => 'Recipient last name',
            'full_name' => 'Recipient full name',
            'email' => 'Recipient email address',
            'company_name' => 'Your company name',
            'unsubscribe_url' => 'Unsubscribe link',
        ];
    }

    public function build()
    {
        $campaign = $this->recipient->campaign;
        $person = $this->recipient->person;

        $unsubscribeUrl = route('laravel-crm.email-tracking.unsubscribe', ['token' => $this->recipient->tracking_token]);
        $trackingPixelUrl = route('laravel-crm.email-tracking.open', ['token' => $this->recipient->tracking_token]);

        $data = [
            'first_name' => $person?->first_name ?? '',
            'last_name' => $person?->last_name ?? '',
            'full_name' => trim(($person?->first_name ?? '').' '.($person?->last_name ?? '')),
            'email' => $this->recipient->address,
            'company_name' => config('app.name'),
            'unsubscribe_url' => $unsubscribeUrl,
            'tracking_pixel_url' => $trackingPixelUrl,
        ];

        $rendered = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($data) {
            return array_key_exists($m[1], $data) ? e($data[$m[1]]) : $m[0];
        }, $campaign->body);

        $rendered = $this->rewriteLinks($rendered);
        $rendered = $this->ensureUnsubscribeFooter($rendered, $unsubscribeUrl);
        $rendered = $this->appendTrackingPixel($rendered, $trackingPixelUrl);

        $from = config('mail.from.address');
        $fromName = config('mail.from.name');

        $mailable = $this->subject($campaign->subject)
            ->to($this->recipient->address)
            ->html($rendered);

        if ($from) {
            $mailable->from($from, $fromName);
        }

        return $mailable;
    }

    private function rewriteLinks(string $html): string
    {
        $token = $this->recipient->tracking_token;

        return preg_replace_callback('/<a\s+([^>]*?)href=(["\'])(.*?)\2([^>]*)>/i', function ($matches) use ($token) {
            $before = $matches[1];
            $quote = $matches[2];
            $url = $matches[3];
            $after = $matches[4];

            if (str_starts_with($url, 'mailto:') || str_starts_with($url, '#')) {
                return $matches[0];
            }

            $trackingUrl = route('laravel-crm.email-tracking.click', ['token' => $token]).'?u='.$this->base64UrlEncode($url);

            return '<a '.$before.'href='.$quote.$trackingUrl.$quote.$after.'>';
        }, $html);
    }

    private function ensureUnsubscribeFooter(string $html, string $unsubscribeUrl): string
    {
        if (str_contains($html, $unsubscribeUrl) || str_contains($html, 'unsubscribe_url')) {
            return $html;
        }

        $footer = '<div style="margin-top:24px;padding-top:16px;border-top:1px solid #eee;font-size:12px;color:#888;text-align:center;">'
            .'<a href="'.$unsubscribeUrl.'" style="color:#888;">Unsubscribe</a>'
            .'</div>';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $footer.'</body>', $html, 1);
        }

        return $html.$footer;
    }

    private function appendTrackingPixel(string $html, string $pixelUrl): string
    {
        $pixel = '<img src="'.$pixelUrl.'" width="1" height="1" alt="" style="display:block;border:0;" />';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1);
        }

        return $html.$pixel;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
