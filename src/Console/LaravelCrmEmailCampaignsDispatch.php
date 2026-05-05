<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use VentureDrake\LaravelCrm\Jobs\SendEmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\EmailCampaign;

class LaravelCrmEmailCampaignsDispatch extends Command
{
    protected $signature = 'laravelcrm:email-campaigns-dispatch';

    protected $description = 'Dispatch scheduled CRM email campaigns whose send time has arrived.';

    public function handle(): int
    {
        $now = Carbon::now('UTC');

        EmailCampaign::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->chunkById(50, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $this->dispatchCampaign($campaign);
                }
            });

        EmailCampaign::query()
            ->where('status', 'sending')
            ->whereDoesntHave('recipients', function ($query) {
                $query->where('status', 'pending');
            })
            ->chunkById(50, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $campaign->update([
                        'status' => 'sent',
                        'sent_at' => Carbon::now('UTC'),
                    ]);

                    $this->info("Campaign {$campaign->campaign_id} marked as sent.");
                }
            });

        return self::SUCCESS;
    }

    private function dispatchCampaign(EmailCampaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        $campaign->recipients()
            ->where('status', 'pending')
            ->chunkById(200, function ($recipients) {
                foreach ($recipients as $recipient) {
                    SendEmailCampaignRecipient::dispatch($recipient);
                }
            });

        $this->info("Dispatched campaign {$campaign->campaign_id}.");
    }
}
