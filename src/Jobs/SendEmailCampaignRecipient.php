<?php

namespace VentureDrake\LaravelCrm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;

class SendEmailCampaignRecipient implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public EmailCampaignRecipient $recipient;

    public function __construct(EmailCampaignRecipient $recipient)
    {
        $this->recipient = $recipient;
    }

    public function handle(): void
    {
        $recipient = $this->recipient->fresh();

        if (! $recipient || $recipient->status !== 'pending') {
            return;
        }

        if ($recipient->email && ! $recipient->email->subscribed) {
            $recipient->update([
                'status' => 'skipped',
                'error' => 'Recipient unsubscribed before send',
            ]);

            return;
        }

        Mail::to($recipient->address)->send(new EmailCampaignMessage($recipient));

        $recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->recipient->update([
            'status' => 'failed',
            'error' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
