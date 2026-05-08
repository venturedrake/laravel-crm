<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\NumberGeneratorService;

class EmailCampaignObserver
{
    public function creating(EmailCampaign $campaign)
    {
        if (! $campaign->external_id) {
            $campaign->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $campaign->user_created_id = auth()->user()->id ?? null;
        }

        $campaign->number = NumberGeneratorService::next(EmailCampaign::class, 1000);

        $campaign->campaign_id = 'EC'.$campaign->number;
    }

    public function updating(EmailCampaign $campaign)
    {
        if (! app()->runningInConsole()) {
            $campaign->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(EmailCampaign $campaign)
    {
        if (! app()->runningInConsole()) {
            $campaign->user_deleted_id = auth()->user()->id ?? null;
            $campaign->saveQuietly();
        }
    }
}
