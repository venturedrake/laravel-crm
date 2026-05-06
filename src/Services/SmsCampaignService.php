<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;

class SmsCampaignService
{
    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function create(array $data): SmsCampaign
    {
        return SmsCampaign::create([
            'name' => $data['name'],
            'body' => $data['body'],
            'from' => $data['from'] ?? null,
            'sms_template_id' => $data['sms_template_id'] ?? null,
            'status' => 'draft',
            'user_owner_id' => auth()->user()->id ?? null,
        ]);
    }

    public function update(array $data, SmsCampaign $campaign): SmsCampaign
    {
        if (! $campaign->isEditable()) {
            throw new \RuntimeException('SMS campaign cannot be edited in its current status.');
        }

        $campaign->update([
            'name' => $data['name'],
            'body' => $data['body'],
            'from' => $data['from'] ?? null,
            'sms_template_id' => $data['sms_template_id'] ?? null,
        ]);

        return $campaign;
    }

    public function schedule(SmsCampaign $campaign, ?string $localScheduledAt): SmsCampaign
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

    public function cancel(SmsCampaign $campaign): SmsCampaign
    {
        if (! $campaign->isCancellable()) {
            return $campaign;
        }

        $campaign->update(['status' => 'cancelled']);

        return $campaign;
    }

    public function materialiseRecipients(SmsCampaign $campaign): int
    {
        $count = 0;
        $seen = [];

        Phone::query()
            ->where('subscribed', true)
            ->where('phoneable_type', Person::class)
            ->whereNotNull('number')
            ->chunkById(200, function ($phones) use ($campaign, &$count, &$seen) {
                foreach ($phones as $phone) {
                    $number = $this->normalize((string) $phone->number);

                    if ($number === '' || isset($seen[$number])) {
                        continue;
                    }

                    $seen[$number] = true;

                    $exists = SmsCampaignRecipient::where('sms_campaign_id', $campaign->id)
                        ->where('phone_id', $phone->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    SmsCampaignRecipient::create([
                        'sms_campaign_id' => $campaign->id,
                        'phone_id' => $phone->id,
                        'person_id' => $phone->phoneable_id,
                        'number' => $phone->number,
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

    private function normalize(string $number): string
    {
        return preg_replace('/[^\d+]/', '', trim($number)) ?? '';
    }

    private function resolveTimezone(): string
    {
        $setting = $this->settingService->get('timezone');

        return $setting ?: 'UTC';
    }
}
