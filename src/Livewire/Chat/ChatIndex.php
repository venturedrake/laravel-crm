<?php

namespace VentureDrake\LaravelCrm\Livewire\Chat;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\ChatConversation;
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
            ['key' => 'visitor_name', 'label' => ucfirst(__('laravel-crm::lang.visitor')), 'sortable' => false],
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
                $c->last_message_preview = \Illuminate\Support\Str::limit($c->latestMessage?->body ?? '', 60);

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

    public function render()
    {
        return view('laravel-crm::livewire.chat.chat-index', [
            'headers' => $this->headers(),
            'conversations' => $this->conversations(),
        ]);
    }
}

