<?php

namespace VentureDrake\LaravelCrm\Observers;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;

class EmailCampaignRecipientObserver
{
    public function creating(EmailCampaignRecipient $recipient)
    {
        if (! $recipient->external_id) {
            $recipient->external_id = Uuid::uuid4()->toString();
        }

        if (! $recipient->tracking_token) {
            $recipient->tracking_token = $this->uniqueToken();
        }
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (EmailCampaignRecipient::where('tracking_token', $token)->exists());

        return $token;
    }
}
