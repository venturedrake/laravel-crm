<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Events\ChatMessageSent;
use VentureDrake\LaravelCrm\Models\ChatMessage;

class ChatMessageObserver
{
    public function creating(ChatMessage $message)
    {
        $message->external_id = Uuid::uuid4()->toString();
    }

    public function created(ChatMessage $message)
    {
        // Touch conversation last_message_at
        $message->conversation?->forceFill([
            'last_message_at' => $message->created_at,
        ])->saveQuietly();

        // Broadcast (no-op if broadcasting driver isn't configured)
        try {
            event(new ChatMessageSent($message));
        } catch (\Throwable $e) {
            // swallow — chat still works without broadcasting
        }
    }
}

