<?php

namespace VentureDrake\LaravelCrm\Livewire\Chat;

use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Services\ChatService;

class ChatShow extends Component
{
    use Toast;

    public ChatConversation $conversation;

    public string $body = '';

    public function mount(ChatConversation $conversation): void
    {
        $this->conversation = $conversation;
        app(ChatService::class)->markRead($conversation, 'visitor');
    }

    /**
     * Echo listener — fired when ChatMessageSent broadcasts.
     * Signature: getListeners() so we can build channel name dynamically.
     */
    public function getListeners(): array
    {
        return [
            'echo:'.$this->conversation->channelName().',.chat.message' => 'onIncomingMessage',
        ];
    }

    public function onIncomingMessage(): void
    {
        // Just trigger a re-render. The DB query will fetch the new message.
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|string|max:5000']);

        app(ChatService::class)->sendAgentMessage(
            $this->conversation,
            auth()->id(),
            $this->body
        );

        $this->body = '';
        $this->success(ucfirst(trans('laravel-crm::lang.message_sent')));
    }

    public function close(): void
    {
        app(ChatService::class)->close($this->conversation);
        $this->success(ucfirst(trans('laravel-crm::lang.chat_closed')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.chat.chat-show', [
            'messages' => $this->conversation->messages()->get(),
        ]);
    }
}

