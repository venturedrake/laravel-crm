<?php

namespace VentureDrake\LaravelCrm\Livewire\Chat;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\ChatService;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class ChatIndex extends Component
{
    use ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $status = 'open';

    #[Url]
    public array $sortBy = ['column' => 'last_message_at', 'direction' => 'desc'];

    public function headers(): array
    {
        return [
            ['key' => 'chat_id', 'label' => '#'],
            ['key' => 'visitor_online', 'label' => '', 'sortable' => false, 'class' => 'w-20'],
            ['key' => 'visitor_name', 'label' => ucfirst(__('laravel-crm::lang.visitor')), 'sortable' => false],
            ['key' => 'unread_count', 'label' => '', 'sortable' => false, 'class' => 'w-12'],
            ['key' => 'last_message_preview', 'label' => ucfirst(__('laravel-crm::lang.last_message')), 'sortable' => false],
            ['key' => 'status', 'label' => ucfirst(__('laravel-crm::lang.status'))],
            ['key' => 'last_message_at', 'label' => ucfirst(__('laravel-crm::lang.updated')), 'format' => fn ($row, $field) => $field?->diffForHumans() ?? '-'],
        ];
    }

    public function conversations(): LengthAwarePaginator
    {
        return ChatConversation::query()
            ->with(['visitor', 'latestMessage'])
            ->when($this->search, fn ($q) => $q->where('chat_id', 'like', "%$this->search%")
                ->orWhere('subject', 'like', "%$this->search%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25)
            ->through(function (ChatConversation $c) {
                $c->visitor_name = $c->visitor?->displayName();
                $c->visitor_online = $c->visitor?->isOnline() ?? false;
                $c->unread_count = $c->unreadForAgents();
                $c->last_message_preview = Str::limit($c->latestMessage?->body ?? '', 60);

                return $c;
            });
    }

    public function delete($id): void
    {
        if ($conversation = ChatConversation::find($id)) {
            $conversation->delete();
            $this->success(ucfirst(trans('laravel-crm::lang.chat_deleted')));
        }
    }

    public function close($id): void
    {
        if ($conversation = ChatConversation::find($id)) {
            app(ChatService::class)->close($conversation);
            $this->success(ucfirst(trans('laravel-crm::lang.chat_closed')));
        }
    }

    public function convertToLead($id): void
    {
        $conversation = ChatConversation::find($id);

        if (! $conversation) {
            return;
        }

        if ($conversation->lead_id) {
            $this->warning(ucfirst(trans('laravel-crm::lang.chat_already_converted')));

            return;
        }

        $visitor = $conversation->visitor;
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

        $conversation->update(['lead_id' => $lead->id]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.chat_converted_to_lead')),
            redirectTo: route('laravel-crm.leads.show', $lead->external_id)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.chat.chat-index', [
            'headers' => $this->headers(),
            'conversations' => $this->conversations(),
        ]);
    }
}
