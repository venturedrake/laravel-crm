<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use VentureDrake\LaravelCrm\Jobs\SendSmsCampaignRecipient;
use VentureDrake\LaravelCrm\Models\SmsCampaign;

class LaravelCrmSmsCampaignsDispatch extends Command
{
    protected $signature = 'laravelcrm:sms-campaigns-dispatch';

    protected $description = 'Dispatch scheduled CRM SMS campaigns whose send time has arrived.';

    public function handle(): int
    {
        $now = Carbon::now('UTC');

        SmsCampaign::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->chunkById(50, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $this->dispatchCampaign($campaign);
                }
            });

        SmsCampaign::query()
            ->where('status', 'sending')
            ->whereDoesntHave('recipients', function ($query) {
                $query->where('status', 'pending');
            })
            ->chunkById(50, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $campaign->refresh();

                    $terminalStatus = $this->resolveTerminalStatus($campaign);

                    $campaign->update([
                        'status' => $terminalStatus,
                        'sent_at' => Carbon::now('UTC'),
                    ]);

                    $this->info("SMS campaign {$campaign->campaign_id} marked as {$terminalStatus}.");
                }
            });

        return self::SUCCESS;
    }

    private function dispatchCampaign(SmsCampaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        $campaign->recipients()
            ->where('status', 'pending')
            ->chunkById(200, function ($recipients) {
                foreach ($recipients as $recipient) {
                    SendSmsCampaignRecipient::dispatch($recipient);
                }
            });

        $this->info("Dispatched SMS campaign {$campaign->campaign_id}.");
    }

    private function resolveTerminalStatus(SmsCampaign $campaign): string
    {
        if ((int) $campaign->total_recipients === 0) {
            return 'failed';
        }

        if ((int) $campaign->sent_count === 0 && (int) $campaign->failed_count > 0) {
            return 'failed';
        }

        return 'sent';
    }
}
