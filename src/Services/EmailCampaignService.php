<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\Person;

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
        $seen = [];

        Email::query()
            ->where('subscribed', true)
            ->where('emailable_type', Person::class)
            ->whereNotNull('address')
            ->chunkById(200, function ($emails) use ($campaign, &$count, &$seen) {
                foreach ($emails as $email) {
                    $address = strtolower(trim((string) $email->address));

                    if ($address === '' || isset($seen[$address])) {
                        continue;
                    }

                    $seen[$address] = true;

                    $exists = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                        ->where('email_id', $email->id)
                        ->exists();

                    if ($exists) {
                        continue;
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
            });

        $campaign->update([
            'total_recipients' => $campaign->recipients()->count(),
        ]);

        return $count;
    }

    private function resolveTimezone(): string
    {
        $setting = $this->settingService->get('timezone');

        return $setting ?: 'UTC';
    }
}
