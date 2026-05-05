<?php

namespace VentureDrake\LaravelCrm\Livewire\Chat;

use Illuminate\Support\Str;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrm\Services\ChatService;

/**
 * Public, unauthenticated visitor-facing chat widget.
 * Loaded inside an iframe via the embed snippet.
 */
class ChatWidgetPanel extends Component
{
    public ChatWidget $widget;

    public ?ChatVisitor $visitor = null;

    public ?ChatConversation $conversation = null;

    public string $body = '';

    public string $visitorName = '';

    public string $visitorEmail = '';

    public function mount(string $publicKey, ?string $visitorToken = null): void
    {
        $this->widget = ChatWidget::where('public_key', $publicKey)
            ->where('is_active', true)
            ->firstOrFail();

        $service = app(ChatService::class);
        $this->visitor = $service->findOrCreateVisitor($this->widget, $visitorToken, [
            'ip' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500),
            'current_url' => request()->header('referer'),
        ]);
        $this->conversation = $service->openConversationForVisitor($this->visitor);
    }

    public function getListeners(): array
    {
        if (! $this->conversation) {
            return [];
        }

        return [
            'echo:'.$this->conversation->channelName().',.chat.message' => 'onIncomingMessage',
        ];
    }

    public function onIncomingMessage(): void
    {
        // refresh on broadcast
    }

    public function updateIdentity(): void
    {
        $this->validate([
            'visitorName' => 'nullable|string|max:120',
            'visitorEmail' => 'nullable|email|max:191',
        ]);

        $this->visitor->update([
            'name' => $this->visitorName ?: $this->visitor->name,
            'email' => $this->visitorEmail ?: $this->visitor->email,
        ]);
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|string|max:5000']);

        app(ChatService::class)->sendVisitorMessage($this->conversation, $this->body);

        $this->body = '';
    }

    public function render()
    {
        return view('laravel-crm::livewire.chat.chat-widget-panel', [
            'messages' => $this->conversation?->messages()->get() ?? collect(),
        ]);
    }
}
