<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\SmsCampaign;

class SmsCampaignObserver
{
    public function creating(SmsCampaign $campaign)
    {
        if (! $campaign->external_id) {
            $campaign->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $campaign->user_created_id = auth()->user()->id ?? null;
        }

        if ($lastCampaign = SmsCampaign::withTrashed()->orderBy('number', 'DESC')->first()) {
            $campaign->number = $lastCampaign->number + 1;
        } else {
            $campaign->number = 1000;
        }

        $campaign->campaign_id = 'SC'.$campaign->number;
    }

    public function updating(SmsCampaign $campaign)
    {
        if (! app()->runningInConsole()) {
            $campaign->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(SmsCampaign $campaign)
    {
        if (! app()->runningInConsole()) {
            $campaign->user_deleted_id = auth()->user()->id ?? null;
            $campaign->saveQuietly();
        }
    }
}
