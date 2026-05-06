<?php

namespace VentureDrake\LaravelCrm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
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
    }

    public function handle(ClickSendService $clickSend): void
    {
        $recipient = $this->recipient->fresh();

        if (! $recipient || $recipient->status !== 'pending') {
            return;
        }

        if ($recipient->phone && ! $recipient->phone->subscribed) {
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
            (string) $recipient->number,
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

            $campaign->increment('sent_count');
        } else {
            $recipient->update([
                'status' => 'failed',
                'error' => mb_substr((string) ($result['error'] ?? 'Unknown error'), 0, 1000),
                'clicksend_message_id' => $result['message_id'] ?? null,
            ]);

            $campaign->increment('failed_count');
        }
    }

    public function failed(Throwable $e): void
    {
        $this->recipient->update([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 1000),
        ]);

        if ($campaign = $this->recipient->campaign) {
            $campaign->increment('failed_count');
        }
    }
}
