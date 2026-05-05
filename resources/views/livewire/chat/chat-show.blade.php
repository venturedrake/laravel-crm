<div class="crm-content" wire:poll.5s>
    <x-mary-header title="{{ $conversation->chat_id }} — {{ $conversation->visitor?->displayName() }}" subtitle="{{ $conversation->visitor?->email }}" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_chat')) }}" link="{{ url(route('laravel-crm.chat.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" />
            @if($conversation->status !== 'closed')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.close_chat')) }}" wire:click="close" icon="o-x-circle" class="btn-sm btn-warning text-white" wire:confirm="Close this conversation?" />
            @endif
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <div class="flex flex-col gap-3 max-h-[60vh] overflow-y-auto p-3">
            @forelse($messages as $m)
                <div class="flex {{ $m->isFromAgent() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[70%] rounded-2xl px-4 py-2 {{ $m->isFromAgent() ? 'bg-primary text-white' : 'bg-base-200' }}">
                        <div class="text-xs opacity-70 mb-1">{{ $m->senderName() }} • {{ $m->created_at->diffForHumans() }}</div>
                        <div class="whitespace-pre-wrap break-words">{{ $m->body }}</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-sm opacity-60 py-10">{{ ucfirst(__('laravel-crm::lang.no_messages_yet')) }}</div>
            @endforelse
        </div>

        @can('reply', $conversation)
            @if($conversation->status !== 'closed')
                <form wire:submit="send" class="flex gap-2 mt-3">
                    <x-mary-textarea wire:model="body" placeholder="{{ ucfirst(__('laravel-crm::lang.type_a_message')) }}..." rows="2" class="flex-1" />
                    <x-mary-button type="submit" label="{{ ucfirst(__('laravel-crm::lang.send')) }}" icon="o-paper-airplane" class="btn-primary text-white" spinner="send" />
                </form>
            @endif
        @endcan
    </x-mary-card>
</div>

