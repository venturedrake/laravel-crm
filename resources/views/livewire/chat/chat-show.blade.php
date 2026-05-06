<div class="crm-content" wire:poll.5s>
    @php($visitor = $conversation->visitor?->fresh())
    @php($online = $visitor?->isOnline() ?? false)
    <x-mary-header
        title="{{ $conversation->chat_id }} — {{ $visitor?->displayName() }}"
        subtitle="{{ $visitor?->email }}"
        separator progress-indicator>
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm">
                @if($online)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.online')) }}" class="badge-success text-white" />
                @else
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.offline')) }}" class="badge-ghost" />
                    @if($visitor?->last_seen_at)
                        <span class="opacity-60 text-xs">{{ $visitor->last_seen_at->diffForHumans() }}</span>
                    @endif
                @endif
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_chat')) }}" link="{{ url(route('laravel-crm.chat.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" />
            @if($conversation->lead_id)
                <x-mary-button
                    label="{{ ucfirst(__('laravel-crm::lang.view_lead')) }}"
                    link="{{ url(route('laravel-crm.leads.show', $conversation->lead->external_id)) }}"
                    icon="fas.user-tag"
                    class="btn-sm btn-success text-white"
                />
            @else
                @can('create', \VentureDrake\LaravelCrm\Models\Lead::class)
                    <x-mary-button
                        label="{{ ucfirst(__('laravel-crm::lang.convert_to_lead')) }}"
                        onclick="modalConvertConversationShow.showModal()"
                        icon="fas.crosshairs"
                        class="btn-sm btn-success text-white"
                    />
                    <dialog id="modalConvertConversationShow" class="modal">
                        <div class="modal-box text-left">
                            <h3 class="text-lg font-bold">{{ ucfirst(__('laravel-crm::lang.convert_to_lead')) }}?</h3>
                            <p class="py-4">{{ ucfirst(__('laravel-crm::lang.convert_to_lead_confirm')) }}</p>
                            <div class="modal-action">
                                <form method="dialog">
                                    <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                                    <button wire:click="convertToLead" class="btn btn-success text-white">{{ ucfirst(__('laravel-crm::lang.convert_to_lead')) }}</button>
                                </form>
                            </div>
                        </div>
                    </dialog>
                @endcan
            @endif
            @if($conversation->status !== 'closed')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.close_chat')) }}" onclick="modalCloseConversationShow.showModal()" icon="o-x-circle" class="btn-sm btn-warning text-white" />
                <dialog id="modalCloseConversationShow" class="modal">
                    <div class="modal-box text-left">
                        <h3 class="text-lg font-bold">{{ ucfirst(__('laravel-crm::lang.close_chat')) }}?</h3>
                        <p class="py-4">{{ __('laravel-crm::lang.close_chat_confirm') }}</p>
                        <div class="modal-action">
                            <form method="dialog">
                                <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                                <button wire:click="close" class="btn btn-warning text-white">{{ ucfirst(__('laravel-crm::lang.close_chat')) }}</button>
                            </form>
                        </div>
                    </div>
                </dialog>
            @endif
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- LEFT: chat (2/3) --}}
        <x-mary-card shadow class="md:col-span-2">
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
                    <form wire:submit="send" class="flex flex-col gap-2 mt-3">
                        <x-mary-textarea wire:model="body" placeholder="{{ ucfirst(__('laravel-crm::lang.type_a_message')) }}..." rows="3" class="w-full" />
                        <div class="flex justify-end">
                            <x-mary-button type="submit" label="{{ ucfirst(__('laravel-crm::lang.send')) }}" icon="o-paper-airplane" class="btn-primary text-white" spinner="send" />
                        </div>
                    </form>
                @endif
            @endcan
        </x-mary-card>

        {{-- RIGHT: visitor info + page-view history (1/3) --}}
        <div class="flex flex-col gap-4">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.visitor')) }}" shadow>
                <dl class="text-sm grid grid-cols-3 gap-y-2">
                    <dt class="opacity-60">{{ ucfirst(__('laravel-crm::lang.name')) }}</dt>
                    <dd class="col-span-2">{{ $visitor?->name ?: '—' }}</dd>

                    <dt class="opacity-60">{{ ucfirst(__('laravel-crm::lang.email')) }}</dt>
                    <dd class="col-span-2">{{ $visitor?->email ?: '—' }}</dd>

                    <dt class="opacity-60">IP</dt>
                    <dd class="col-span-2 font-mono text-xs">{{ $visitor?->ip_address ?: '—' }}</dd>

                    <dt class="opacity-60">{{ ucfirst(__('laravel-crm::lang.last_active')) }}</dt>
                    <dd class="col-span-2">{{ $visitor?->last_seen_at?->diffForHumans() ?: '—' }}</dd>

                    @if($conversation->lead_id)
                        <dt class="opacity-60">{{ ucfirst(__('laravel-crm::lang.lead')) }}</dt>
                        <dd class="col-span-2">
                            <a href="{{ url(route('laravel-crm.leads.show', $conversation->lead->external_id)) }}" class="link link-primary text-xs font-medium">
                                {{ $conversation->lead->lead_id ?? $conversation->lead->title }}
                            </a>
                        </dd>
                    @endif
                </dl>
            </x-mary-card>

            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.page_history')) }}" shadow>
                @if($pageViews->isEmpty() && $pageViewPage === 1)
                    <div class="text-sm opacity-60 text-center py-6">{{ ucfirst(__('laravel-crm::lang.no_pages_viewed')) }}</div>
                @else
                    <ul class="divide-y divide-base-200 -mx-4">
                        @foreach($pageViews as $v)
                            <li class="px-4 py-2 text-sm">
                                <div class="font-medium truncate" title="{{ $v->title ?: '—' }}">
                                    {{ $v->title ?: '(untitled)' }}
                                </div>
                                <a href="{{ $v->url }}" target="_blank" rel="noopener noreferrer"
                                   class="link link-hover text-xs opacity-70 break-all block mt-0.5"
                                   title="{{ $v->url }}">
                                    {{ $v->url }}
                                </a>
                                <div class="text-xs opacity-50 mt-1">{{ $v->viewed_at->diffForHumans() }}</div>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Pagination --}}
                    @if($pageViewTotal > 1)
                        <div class="flex items-center justify-between mt-3 text-xs">
                            <x-mary-button
                                icon="o-chevron-left"
                                class="btn-xs btn-ghost"
                                wire:click="pageViewPrev"
                                :disabled="$pageViewPage <= 1"
                            />
                            <span class="opacity-60">{{ $pageViewPage }} / {{ $pageViewTotal }}</span>
                            <x-mary-button
                                icon="o-chevron-right"
                                class="btn-xs btn-ghost"
                                wire:click="pageViewNext({{ $pageViewTotal }})"
                                :disabled="$pageViewPage >= $pageViewTotal"
                            />
                        </div>
                    @endif
                @endif
            </x-mary-card>
        </div>
    </div>
</div>

