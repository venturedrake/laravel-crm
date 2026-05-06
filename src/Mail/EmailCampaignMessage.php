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

        $bodyContent = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($textData) {
            return array_key_exists($m[1], $textData) ? e($textData[$m[1]]) : $m[0];
        }, $campaign->body);

        $bodyContent = $this->rewriteLinks($bodyContent);

        $rendered = $this->wrapInShell(
            $bodyContent,
            $this->resolveLogoHtml(),
            $this->resolveAddressLine($campaign->team_id),
            $unsubscribeUrl
        );

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

        return preg_replace_callback('/<a\s+([^>]*?)(?<=\s)href=(["\'])(.*?)\2([^>]*)>/i', function ($matches) use ($token) {
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

    private function wrapInShell(string $body, string $logoHtml, string $addressLine, string $unsubscribeUrl): string
    {
        $addressBlock = $addressLine !== ''
            ? '<div style="margin-bottom:8px;">'.e($addressLine).'</div>'
            : '';

        $unsubscribeLink = '<a href="'.e($unsubscribeUrl).'" style="color:#6b7280;text-decoration:underline;">Unsubscribe</a>';

        return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:24px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;">
        <tr>
          <td style="padding:24px 32px;border-bottom:1px solid #e5e7eb;text-align:center;">
            {$logoHtml}
          </td>
        </tr>
        <tr>
          <td style="padding:32px;color:#374151;font-size:15px;line-height:1.6;">
            {$body}
          </td>
        </tr>
        <tr>
          <td style="padding:20px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.5;text-align:center;">
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
HTML;
    }

    private function appendTrackingPixel(string $html, string $pixelUrl): string
    {
        $pixel = '<img src="'.e($pixelUrl).'" width="1" height="1" alt="" style="display:block;border:0;" />';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1);
        }

        return $html.$pixel;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function resolveLogoHtml(): string
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

    private function resolveAddressLine(?int $teamId): string
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
