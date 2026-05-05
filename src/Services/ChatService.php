<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatMessage;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
use VentureDrake\LaravelCrm\Models\ChatVisitorPageView;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrm\Repositories\ChatRepository;

class ChatService
{
    private ChatRepository $repository;

    public function __construct(ChatRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Find or create the visitor record for a widget + visitor token.
     */
    public function findOrCreateVisitor(ChatWidget $widget, ?string $visitorToken, array $context = []): ChatVisitor
    {
        $visitor = $visitorToken
            ? ChatVisitor::where('chat_widget_id', $widget->id)->where('visitor_token', $visitorToken)->first()
            : null;

        if (! $visitor) {
            $visitor = ChatVisitor::create([
                'chat_widget_id' => $widget->id,
                'team_id' => $widget->team_id,
                'ip_address' => $context['ip'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'current_url' => $context['current_url'] ?? null,
            ]);
        }

        $visitor->forceFill([
            'last_seen_at' => now(),
            'current_url' => $context['current_url'] ?? $visitor->current_url,
        ])->save();

        return $visitor;
    }

    /**
     * Get or open a conversation for the visitor.
     */
    public function openConversationForVisitor(ChatVisitor $visitor): ChatConversation
    {
        $conversation = $visitor->conversations()
            ->where('status', '!=', 'closed')
            ->latest()
            ->first();

        if (! $conversation) {
            $conversation = ChatConversation::create([
                'chat_widget_id' => $visitor->chat_widget_id,
                'chat_visitor_id' => $visitor->id,
                'team_id' => $visitor->team_id,
                'status' => 'open',
            ]);
        }

        return $conversation;
    }

    public function sendVisitorMessage(ChatConversation $conversation, string $body): ChatMessage
    {
        return ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'team_id' => $conversation->team_id,
            'sender_type' => 'visitor',
            'sender_id' => $conversation->chat_visitor_id,
            'body' => $body,
        ]);
    }

    public function sendAgentMessage(ChatConversation $conversation, int $userId, string $body): ChatMessage
    {
        $message = ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'team_id' => $conversation->team_id,
            'sender_type' => 'user',
            'sender_id' => $userId,
            'body' => $body,
        ]);

        if (! $conversation->user_assigned_id) {
            $conversation->update(['user_assigned_id' => $userId]);
        }

        return $message;
    }

    public function markRead(ChatConversation $conversation, string $forSender = 'visitor'): void
    {
        $conversation->messages()
            ->where('sender_type', $forSender)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark all agent messages in the conversation as read by the visitor.
     */
    public function markReadByVisitor(ChatConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', 'user')
            ->whereNull('visitor_read_at')
            ->update(['visitor_read_at' => now()]);
    }

    /**
     * Count agent messages the visitor has NOT yet read.
     */
    public function unreadForVisitor(ChatConversation $conversation): int
    {
        return $conversation->messages()
            ->where('sender_type', 'user')
            ->whereNull('visitor_read_at')
            ->count();
    }

    public function close(ChatConversation $conversation): void
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Record a page view for the visitor. De-dupes against the latest entry —
     * we don't insert if the URL matches the most recent view.
     */
    public function recordPageView(ChatVisitor $visitor, ?string $url, ?string $title = null): ?ChatVisitorPageView
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        // Cap field lengths defensively
        $url = mb_substr($url, 0, 2048);
        $title = $title !== null ? mb_substr(trim($title), 0, 512) : null;

        $latest = $visitor->pageViews()->first();
        if ($latest && $latest->url === $url) {
            // Same page — just bump viewed_at so "current page" stays accurate
            $latest->update(['viewed_at' => now()]);

            return $latest;
        }

        return ChatVisitorPageView::create([
            'chat_visitor_id' => $visitor->id,
            'team_id' => $visitor->team_id,
            'url' => $url,
            'title' => $title,
            'viewed_at' => now(),
        ]);
    }
}
