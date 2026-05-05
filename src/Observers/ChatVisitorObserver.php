<?php

namespace VentureDrake\LaravelCrm\Observers;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ChatVisitor;

class ChatVisitorObserver
{
    public function creating(ChatVisitor $visitor)
    {
        $visitor->external_id = Uuid::uuid4()->toString();

        if (! $visitor->visitor_token) {
            $visitor->visitor_token = Str::random(40);
        }
    }
}
