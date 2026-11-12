<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Support\EmailCampaignAudience;

class EmailCampaignService
{
    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function create(array $data): EmailCampaign
    {
        return EmailCampaign::create([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'preview_text' => $data['preview_text'] ?? null,
            'body' => $data['body'],
            'email_template_id' => $data['email_template_id'] ?? null,
            ...$this->audienceAttributes($data),
            'status' => 'draft',
            'user_owner_id' => auth()->user()->id ?? null,
        ]);
    }

    public function update(array $data, EmailCampaign $campaign): EmailCampaign
    {
        if (! $campaign->isEditable()) {
            throw new \RuntimeException('Email campaign cannot be edited in its current status.');
        }

        $campaign->update([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'preview_text' => $data['preview_text'] ?? null,
            'body' => $data['body'],
            'email_template_id' => $data['email_template_id'] ?? null,
            ...(array_key_exists('audience_type', $data) ? $this->audienceAttributes($data) : []),
        ]);

        return $campaign;
    }

    public function schedule(EmailCampaign $campaign, ?string $localScheduledAt): EmailCampaign
    {
        $timezone = $this->resolveTimezone();

        if ($localScheduledAt) {
            $scheduledAt = Carbon::parse($localScheduledAt, $timezone)->setTimezone('UTC');
        } else {
            $scheduledAt = Carbon::now('UTC');
        }

        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'timezone' => $timezone,
        ]);

        $this->materialiseRecipients($campaign);

        return $campaign->fresh();
    }

    public function cancel(EmailCampaign $campaign): EmailCampaign
    {
        if (! $campaign->isCancellable()) {
            return $campaign;
        }

        $campaign->update(['status' => 'cancelled']);

        return $campaign;
    }

    public function materialiseRecipients(EmailCampaign $campaign): int
    {
        $count = 0;

        // One query for the rows already there, not one per address.
        $existing = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->pluck('email_id')
            ->filter()
            ->flip()
            ->all();

        EmailCampaignAudience::each(
            $campaign->audience_type ?: EmailCampaign::AUDIENCE_ALL,
            $campaign->audience_ids ?? [],
            function ($email) use ($campaign, &$count, $existing) {
                if (isset($existing[$email->id])) {
                    return;
                }

                EmailCampaignRecipient::create([
                    'email_campaign_id' => $campaign->id,
                    'email_id' => $email->id,
                    'person_id' => $email->emailable_id,
                    'address' => $email->address,
                    'team_id' => $campaign->team_id,
                    'status' => 'pending',
                ]);

                $count++;
            }
        );

        $campaign->update([
            'total_recipients' => $campaign->recipients()->count(),
        ]);

        return $count;
    }

    /**
     * The audience columns. A campaign that targets everyone stores no ids.
     */
    private function audienceAttributes(array $data): array
    {
        $type = $data['audience_type'] ?? EmailCampaign::AUDIENCE_ALL;

        if (! in_array($type, EmailCampaign::AUDIENCE_TYPES, true)) {
            $type = EmailCampaign::AUDIENCE_ALL;
        }

        return [
            'audience_type' => $type,
            'audience_ids' => $type === EmailCampaign::AUDIENCE_ALL
                ? null
                : array_values(array_map('intval', $data['audience_ids'] ?? [])),
        ];
    }

    private function resolveTimezone(): string
    {
        $setting = $this->settingService->get('timezone');

        return $setting ?: 'UTC';
    }
}
