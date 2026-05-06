<?php

namespace VentureDrake\LaravelCrm\Observers;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;

class SmsCampaignRecipientObserver
{
    public function creating(SmsCampaignRecipient $recipient)
    {
        if (! $recipient->external_id) {
            $recipient->external_id = Uuid::uuid4()->toString();
        }

        // Rely on the DB unique index for collision safety. With 40 random chars
        // (~238 bits of entropy from Str::random) practical collisions never
        // happen, and the TOCTOU window of a check-then-insert is itself unsafe
        // under concurrent inserts.
        if (! $recipient->tracking_token) {
            $recipient->tracking_token = Str::random(40);
        }
    }
}
