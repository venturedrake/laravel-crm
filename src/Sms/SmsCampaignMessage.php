<?php

namespace VentureDrake\LaravelCrm\Sms;

use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;

class SmsCampaignMessage
{
    public static function availablePlaceholders(): array
    {
        return [
            'first_name' => 'Recipient first name',
            'last_name' => 'Recipient last name',
            'full_name' => 'Recipient full name',
            'company_name' => 'Your company name',
        ];
    }

    /**
     * Build the final SMS body that will be sent to the recipient — with
     * placeholders substituted, links rewritten through the click-tracker,
     * and an unsubscribe URL appended.
     */
    public static function renderBody(SmsCampaignRecipient $recipient): string
    {
        $campaign = $recipient->campaign;
        $person = $recipient->person;

        $data = [
            'first_name' => $person?->first_name ?? '',
            'last_name' => $person?->last_name ?? '',
            'full_name' => trim(($person?->first_name ?? '').' '.($person?->last_name ?? '')),
            'company_name' => config('app.name'),
        ];

        $body = self::substitute($campaign->body ?? '', $data);
        $body = self::rewriteLinks($body, $recipient->tracking_token);

        $unsubscribeUrl = route('laravel-crm.sms-tracking.unsubscribe', ['token' => $recipient->tracking_token]);

        return rtrim($body)."\nReply STOP to opt out or visit ".$unsubscribeUrl;
    }

    /**
     * Render a sample body for the preview drawer using placeholder sample data.
     */
    public static function renderPreview(string $body): string
    {
        $sample = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'full_name' => 'Jane Smith',
            'company_name' => config('app.name'),
        ];

        return rtrim(self::substitute($body, $sample))."\nReply STOP to opt out or visit https://example.com/unsubscribe";
    }

    /**
     * Estimate how many GSM-7 segments the message takes. SMS over 160 chars
     * is split into multi-part segments of 153 chars each.
     */
    public static function segmentCount(string $body): int
    {
        $length = mb_strlen($body);

        if ($length === 0) {
            return 0;
        }

        if ($length <= 160) {
            return 1;
        }

        return (int) ceil($length / 153);
    }

    private static function substitute(string $text, array $data): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($data) {
            return array_key_exists($m[1], $data) ? $data[$m[1]] : $m[0];
        }, $text);
    }

    private static function rewriteLinks(string $body, string $token): string
    {
        return preg_replace_callback('#https?://\S+#i', function ($matches) use ($token) {
            $url = rtrim($matches[0], ".,;:!?\"')]");
            $trailing = substr($matches[0], strlen($url));

            $tracking = route('laravel-crm.sms-tracking.click', ['token' => $token]).'?u='.self::base64UrlEncode($url);

            return $tracking.$trailing;
        }, $body);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
