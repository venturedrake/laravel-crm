<?php

namespace VentureDrake\LaravelCrm\Livewire\Chat;

use Livewire\Component;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\ChatService;

class ChatShow extends Component
{
    use Toast;

    public ChatConversation $conversation;

    public string $body = '';

    public int $pageViewPage = 1;

    private const PAGE_VIEW_PER_PAGE = 10;

    public function mount(ChatConversation $conversation): void
    {
        $this->conversation = $conversation;
        app(ChatService::class)->markRead($conversation, 'visitor');
    }

    public function getListeners(): array
    {
        return [
            'echo:'.$this->conversation->channelName().',.chat.message' => 'onIncomingMessage',
        ];
    }

    public function onIncomingMessage(): void {}

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

    public function convertToLead(): void
    {
        // Prevent double-conversion
        if ($this->conversation->lead_id) {
            $this->warning(ucfirst(trans('laravel-crm::lang.chat_already_converted')));

            return;
        }

        $visitor = $this->conversation->visitor;

        // Reuse existing Person if the visitor was already linked, or create one
        $person = $visitor?->person;

        if (! $person && ($visitor?->name || $visitor?->email)) {
            $nameParts = $visitor->name ? explode(' ', trim($visitor->name), 2) : [];

            $person = Person::create([
                'external_id' => Uuid::uuid4()->toString(),
                'first_name' => $nameParts[0] ?? null,
                'last_name' => $nameParts[1] ?? null,
                'user_created_id' => auth()->id(),
                'user_updated_id' => auth()->id(),
            ]);

            if ($visitor->email) {
                $person->emails()->create([
                    'address' => $visitor->email,
                    'primary' => true,
                    'user_created_id' => auth()->id(),
                    'user_updated_id' => auth()->id(),
                ]);
            }

            // Link visitor → person so future conversions reuse it
            $visitor->update(['person_id' => $person->id]);
        }

        $title = $visitor?->name
            ? ucfirst(trans('laravel-crm::lang.chat_lead_title', ['name' => $visitor->name]))
            : ucfirst(trans('laravel-crm::lang.chat_lead_title_unknown'));

        $lead = Lead::create([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $title,
            'person_id' => $person?->id,
            'lead_status_id' => 1,
            'user_owner_id' => auth()->id(),
            'user_created_id' => auth()->id(),
            'user_updated_id' => auth()->id(),
        ]);

        $this->conversation->update(['lead_id' => $lead->id]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.chat_converted_to_lead')),
            redirectTo: route('laravel-crm.leads.show', $lead->external_id)
        );
    }

    public function pageViewPrev(): void
    {
        if ($this->pageViewPage > 1) {
            $this->pageViewPage--;
        }
    }

    public function pageViewNext(int $totalPages): void
    {
        if ($this->pageViewPage < $totalPages) {
            $this->pageViewPage++;
        }
    }

    public function render()
    {
        // Re-mark visitor messages as read on every render (covers wire:poll updates)
        app(ChatService::class)->markRead($this->conversation, 'visitor');

        $visitor = $this->conversation->visitor?->fresh();

        $pageViewsQuery = $visitor?->pageViews();
        $totalPageViews = $pageViewsQuery ? $pageViewsQuery->count() : 0;
        $totalPages = (int) ceil($totalPageViews / self::PAGE_VIEW_PER_PAGE) ?: 1;

        if ($this->pageViewPage > $totalPages) {
            $this->pageViewPage = $totalPages;
        }

        $pageViews = $pageViewsQuery
            ? $pageViewsQuery
                ->offset(($this->pageViewPage - 1) * self::PAGE_VIEW_PER_PAGE)
                ->limit(self::PAGE_VIEW_PER_PAGE)
                ->get()
            : collect();

        return view('laravel-crm::livewire.chat.chat-show', [
            'messages' => $this->conversation->messages()->get(),
            'pageViews' => $pageViews,
            'pageViewPage' => $this->pageViewPage,
            'pageViewTotal' => $totalPages,
            'unreadCount' => $this->conversation->unreadForAgents(),
        ]);
    }
}
