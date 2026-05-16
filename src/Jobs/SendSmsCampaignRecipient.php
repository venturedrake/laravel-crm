<?php

namespace VentureDrake\LaravelCrm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

class SendSmsCampaignRecipient implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public SmsCampaignRecipient $recipient;

    public function __construct(SmsCampaignRecipient $recipient)
    {
        $this->recipient = $recipient;
        $this->onQueue('sms');
    }

    public function handle(ClickSendService $clickSend): void
    {
        $recipient = $this->recipient->fresh();

        if (! $recipient || $recipient->status !== 'pending') {
            return;
        }

        if (! $recipient->phone) {
            $recipient->update([
                'status' => 'skipped',
                'error' => 'Phone record missing',
            ]);

            return;
        }

        if (! $recipient->phone->subscribed) {
            $recipient->update([
                'status' => 'skipped',
                'error' => 'Recipient unsubscribed before send',
            ]);

            return;
        }

        $campaign = $recipient->campaign;

        $from = $campaign->from
            ?: $clickSend->defaultFrom();

        $body = SmsCampaignMessage::renderBody($recipient);

        $result = $clickSend->sendSms(
            (string) $recipient->phone->number,
            $body,
            $from,
            $recipient->tracking_token,
        );

        if ($result['ok']) {
            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'clicksend_message_id' => $result['message_id'],
            ]);

            $this->bumpCampaignCounter($campaign->id, 'sent_count');
        } else {
            $recipient->update([
                'status' => 'failed',
                'error' => mb_substr((string) ($result['error'] ?? 'Unknown error'), 0, 1000),
                'clicksend_message_id' => $result['message_id'] ?? null,
            ]);

            $this->bumpCampaignCounter($campaign->id, 'failed_count');
        }
    }

    public function failed(Throwable $e): void
    {
        $recipient = $this->recipient->fresh();

        // handle() may have already moved this recipient to a terminal state and
        // bumped the campaign counter. Avoid double-counting on queue retry exhaust.
        if (! $recipient || in_array($recipient->status, ['sent', 'failed', 'skipped'], true)) {
            return;
        }

        $recipient->update([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 1000),
        ]);

        if ($campaign = $recipient->campaign) {
            $this->bumpCampaignCounter($campaign->id, 'failed_count');
        }
    }

    /**
     * Increment a counter on the campaign atomically without firing model events
     * to avoid race conditions with concurrent writes.
     */
    private function bumpCampaignCounter(int $campaignId, string $column): void
    {
        SmsCampaign::where('id', $campaignId)->increment($column);
    }
}
