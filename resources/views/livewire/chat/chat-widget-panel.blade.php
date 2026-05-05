<div wire:poll.4s class="flex flex-col h-screen">
    {{-- Header --}}
    <div class="px-4 py-3 text-white" style="background: {{ $widget->color ?: '#2563eb' }};">
        <div class="font-semibold">{{ $widget->name }}</div>
        @if($widget->welcome_message)
            <div class="text-xs opacity-90">{{ $widget->welcome_message }}</div>
        @endif
    </div>

    {{-- Identity capture --}}
    @if(!$visitor->name || !$visitor->email)
        <form wire:submit="updateIdentity" class="p-3 border-b bg-base-200 grid gap-2">
            <input type="text" wire:model="visitorName" placeholder="Your name" class="input input-sm input-bordered w-full" />
            <input type="email" wire:model="visitorEmail" placeholder="Your email" class="input input-sm input-bordered w-full" />
            <button type="submit" class="btn btn-sm btn-primary">Start chat</button>
        </form>
    @endif

    {{-- Messages --}}
    <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-2">
        @forelse($messages as $m)
            <div class="flex {{ $m->isFromVisitor() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm
                    {{ $m->isFromVisitor() ? 'text-white' : 'bg-base-200' }}"
                    @if($m->isFromVisitor()) style="background: {{ $widget->color ?: '#2563eb' }};" @endif>
                    <div class="whitespace-pre-wrap break-words">{{ $m->body }}</div>
                    <div class="text-[10px] opacity-70 mt-1">{{ $m->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <div class="text-center text-xs opacity-60 mt-8">{{ $widget->welcome_message ?: 'How can we help?' }}</div>
        @endforelse
    </div>

    {{-- Composer --}}
    <form wire:submit="send" class="border-t p-2 flex gap-2">
        <input type="text" wire:model="body" placeholder="Type a message..." class="input input-sm input-bordered flex-1" />
        <button type="submit" class="btn btn-sm btn-primary" style="background: {{ $widget->color ?: '#2563eb' }}; border-color: {{ $widget->color ?: '#2563eb' }};">Send</button>
    </form>
</div>

