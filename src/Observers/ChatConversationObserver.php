<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ChatConversation;

class ChatConversationObserver
{
    public function creating(ChatConversation $conversation)
    {
        $conversation->external_id = Uuid::uuid4()->toString();

        // Auto-generate human ID like C1001
        if (! $conversation->chat_id) {
            $last = ChatConversation::withoutGlobalScopes()->withTrashed()->max('id') ?? 0;
            $conversation->chat_id = 'C'.(1000 + $last + 1);
        }

        if (! app()->runningInConsole()) {
            $conversation->user_created_id = auth()->user()->id ?? null;
        }
    }

    public function updating(ChatConversation $conversation)
    {
        if (! app()->runningInConsole()) {
            $conversation->user_updated_id = auth()->user()->id ?? null;
        }
    }

    public function deleting(ChatConversation $conversation)
    {
        if (! app()->runningInConsole()) {
            $conversation->user_deleted_id = auth()->user()->id ?? null;
            $conversation->saveQuietly();
        }
    }
}
