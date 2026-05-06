<?php

namespace VentureDrake\LaravelCrm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;

class MaterialiseSmsCampaignRecipients implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public SmsCampaign $campaign;

    public function __construct(SmsCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle(SmsCampaignService $service): void
    {
        $service->materialiseRecipients($this->campaign);
    }
}
