<?php

namespace VentureDrake\LaravelCrm\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\Setting;

use function VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine;

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
        ];
    }

    public function build()
    {
        $campaign = $this->recipient->campaign;
        $person = $this->recipient->person;

        $unsubscribeUrl = route('laravel-crm.email-tracking.unsubscribe', ['token' => $this->recipient->tracking_token]);
        $trackingPixelUrl = route('laravel-crm.email-tracking.open', ['token' => $this->recipient->tracking_token]);

        $textData = [
            'first_name' => $person?->first_name ?? '',
            'last_name' => $person?->last_name ?? '',
            'full_name' => trim(($person?->first_name ?? '').' '.($person?->last_name ?? '')),
            'email' => $this->recipient->address,
            'company_name' => config('app.name'),
        ];

        $substitute = fn ($text) => preg_replace_callback('/\{(\w+)\}/', function ($m) use ($textData) {
            return array_key_exists($m[1], $textData) ? e($textData[$m[1]]) : $m[0];
        }, $text);

        $bodyContent = $this->rewriteLinks($substitute($campaign->body));

        $rendered = self::wrapInShell(
            $bodyContent,
            self::resolveLogoHtml(),
            self::resolveAddressLine($campaign->team_id),
            $unsubscribeUrl,
            $substitute($campaign->preview_text ?? '')
        );

        $rendered = self::appendTrackingPixel($rendered, $trackingPixelUrl);

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

        return preg_replace_callback('/<a\s+([^>]*?)(?<=\s)href=(["\'])(.*?)\2([^>]*)>/i', function ($matches) use ($token) {
            $before = $matches[1];
            $quote = $matches[2];
            $url = $matches[3];
            $after = $matches[4];

            if (str_starts_with($url, 'mailto:') || str_starts_with($url, '#')) {
                return $matches[0];
            }

            $trackingUrl = route('laravel-crm.email-tracking.click', ['token' => $token]).'?u='.self::base64UrlEncode($url);

            return '<a '.$before.'href='.$quote.$trackingUrl.$quote.$after.'>';
        }, $html);
    }

    public static function renderPreview(string $body, string $previewText = '', ?int $teamId = null): string
    {
        $sampleData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'full_name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'company_name' => config('app.name'),
        ];

        $bodyContent = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($sampleData) {
            return array_key_exists($m[1], $sampleData) ? e($sampleData[$m[1]]) : $m[0];
        }, $body);

        return self::wrapInShell(
            $bodyContent,
            self::resolveLogoHtml(),
            self::resolveAddressLine($teamId),
            '#',
            $previewText
        );
    }

    private static function wrapInShell(string $body, string $logoHtml, string $addressLine, string $unsubscribeUrl, string $previewText = ''): string
    {
        $previewBlock = $previewText !== ''
            ? '<span style="display:none;font-size:1px;color:#f9fafb;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
                .e($previewText)
                .str_repeat('&nbsp;&#847;&zwnj;&nbsp;', 30)
                .'</span>'
            : '';

        $addressBlock = $addressLine !== ''
            ? '<div style="margin-bottom:8px;">'.e($addressLine).'</div>'
            : '';

        $unsubscribeLink = '<a href="'.e($unsubscribeUrl).'" style="color:#6b7280;text-decoration:underline;">Unsubscribe</a>';

        $lang = app()->getLocale();

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title></title>
<style>
  img { max-width:100% !important; height:auto; }
  a { word-break:break-word; }
  @media only screen and (max-width:600px) {
    .email-outer { padding:0 !important; }
    .email-card { border-radius:0 !important; width:100% !important; }
    .email-header-td { padding:16px !important; }
    .email-content-td { padding:20px 16px !important; font-size:14px !important; }
    .email-footer-td { padding:16px !important; }
    h1 { font-size:22px !important; }
    h2 { font-size:18px !important; }
    .col { display:block !important; width:100% !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;">
{$previewBlock}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="email-outer" style="background:#f5f7fa;padding:24px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <tr>
    <td align="center" valign="top">
      <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" class="email-card" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:600px;">
        <tr>
          <td class="email-header-td" style="padding:24px 32px;border-bottom:1px solid #e5e7eb;text-align:center;">
            {$logoHtml}
          </td>
        </tr>
        <tr>
          <td class="email-content-td" style="padding:32px;color:#374151;font-size:15px;line-height:1.6;">
            {$body}
          </td>
        </tr>
        <tr>
          <td class="email-footer-td" style="padding:20px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.5;text-align:center;">
            {$addressBlock}
            <div style="margin-bottom:12px;">{$unsubscribeLink}</div>
            <div style="color:#9ca3af;font-size:11px;">
              Powered by <a href="https://laravelcrm.com" style="color:#9ca3af;text-decoration:underline;">Laravel CRM</a>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }

    private static function appendTrackingPixel(string $html, string $pixelUrl): string
    {
        $pixel = '<img src="'.e($pixelUrl).'" width="1" height="1" alt="" style="display:block;border:0;" />';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1);
        }

        return $html.$pixel;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function resolveLogoHtml(): string
    {
        $logoFile = app('laravel-crm.settings')->get('logo_file');
        $companyName = config('app.name');

        if ($logoFile) {
            return '<img src="'.e(asset('storage/'.$logoFile)).'" alt="'.e($companyName).'" style="max-height:60px;max-width:240px;display:inline-block;border:0;" />';
        }

        return '<span style="display:inline-block;font-size:24px;font-weight:700;color:#111827;letter-spacing:-0.5px;">'
            .e($companyName)
            .'</span>';
    }

    private static function resolveAddressLine(?int $teamId): string
    {
        $teamSetting = Setting::withoutGlobalScopes()
            ->where('name', 'team')
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->first();

        if (! $teamSetting) {
            return '';
        }

        $businessId = AddressType::where('name', 'Business')->value('id');
        $currentId = AddressType::where('name', 'Current')->value('id');

        $address = ($businessId ? $teamSetting->addresses()->where('address_type_id', $businessId)->first() : null)
            ?? ($currentId ? $teamSetting->addresses()->where('address_type_id', $currentId)->first() : null);

        return $address ? addressSingleLine($address) : '';
    }
}
