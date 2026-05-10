<?php

namespace VentureDrake\LaravelCrm\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatMessage;
use VentureDrake\LaravelCrm\Models\ChatVisitor;

class ChatRequestInitiated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public ChatConversation $conversation;

    public ?ChatVisitor $visitor;

    public ChatMessage $message;

    public function __construct(ChatConversation $conversation, ?ChatVisitor $visitor, ChatMessage $message)
    {
        $this->conversation = $conversation;
        $this->visitor = $visitor;
        $this->message = $message;
    }

    public function build(): self
    {
        return $this
            ->subject(ucfirst(trans('laravel-crm::lang.chat_request_initiated_subject')))
            ->markdown('laravel-crm::mail.chat-request-initiated');
    }
}
