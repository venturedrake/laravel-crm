<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatMessage;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
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

    public function close(ChatConversation $conversation): void
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}

