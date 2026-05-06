<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Jobs\MaterialiseSmsCampaignRecipients;
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
        if ($campaign->status !== 'draft') {
            throw new \RuntimeException('SMS campaign cannot be scheduled in its current status.');
        }

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

        // Materialisation walks every Phone in the tenant — push it onto the
        // queue so the HTTP request returning the user to the show page does
        // not block on what can be a multi-minute job for large tenants.
        MaterialiseSmsCampaignRecipients::dispatch($campaign);

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
        $seen = [];
        $rows = [];
        $now = Carbon::now();

        $flush = function () use (&$rows) {
            if ($rows === []) {
                return;
            }

            // The unique index on (sms_campaign_id, phone_id) makes this
            // idempotent under retries; insertOrIgnore skips collisions
            // without throwing.
            SmsCampaignRecipient::query()->insertOrIgnore($rows);

            $rows = [];
        };

        Phone::query()
            ->where('subscribed', true)
            ->where('phoneable_type', Person::class)
            ->whereNotNull('number')
            ->chunkById(500, function ($phones) use ($campaign, &$seen, &$rows, $flush, $now) {
                foreach ($phones as $phone) {
                    $number = $this->normalize((string) $phone->number);

                    if ($number === '' || isset($seen[$number])) {
                        continue;
                    }

                    $seen[$number] = true;

                    $rows[] = [
                        'external_id' => Uuid::uuid4()->toString(),
                        'sms_campaign_id' => $campaign->id,
                        'phone_id' => $phone->id,
                        'person_id' => $phone->phoneable_id,
                        'team_id' => $campaign->team_id,
                        'tracking_token' => Str::random(40),
                        'status' => 'pending',
                        'clicks_count' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($rows) >= 500) {
                        $flush();
                    }
                }
            });

        $flush();

        $total = $campaign->recipients()->count();

        $campaign->update([
            'total_recipients' => $total,
        ]);

        return $total;
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
