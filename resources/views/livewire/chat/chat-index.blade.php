<div class="crm-content" wire:poll.10s>
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.chat')) }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_chat')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-select wire:model.live="status" :options="[
                ['id' => 'open', 'name' => ucfirst(__('laravel-crm::lang.open'))],
                ['id' => 'pending', 'name' => ucfirst(__('laravel-crm::lang.pending'))],
                ['id' => 'closed', 'name' => ucfirst(__('laravel-crm::lang.closed'))],
                ['id' => '', 'name' => ucfirst(__('laravel-crm::lang.all'))],
            ]" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$conversations" :link="route('laravel-crm.chat.show', ['chat' => '[id]'])" with-pagination class="whitespace-nowrap">
            @scope('cell_visitor_online', $c)
                @if($c->visitor_online)
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.online')) }}" class="badge-success badge-sm text-white" />
                @else
                    <x-mary-badge value="{{ ucfirst(__('laravel-crm::lang.offline')) }}" class="badge-ghost badge-sm" />
                @endif
            @endscope
            @scope('cell_unread_count', $c)
                @if($c->unread_count > 0)
                    <x-mary-badge value="{{ $c->unread_count > 99 ? '99+' : $c->unread_count }}" class="badge-error badge-sm text-white" />
                @endif
            @endscope
            @scope('cell_status', $c)
                <x-mary-badge value="{{ ucfirst($c->status) }}" class="{{ $c->status === 'open' ? 'badge-success' : ($c->status === 'pending' ? 'badge-warning' : 'badge-neutral') }} text-white" />
            @endscope
            @scope('actions', $c)
                <div class="flex gap-1 justify-end">
                    @can('view crm chat')
                        <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.chat.show', $c)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('create', \VentureDrake\LaravelCrm\Models\Lead::class)
                        @if(!$c->lead_id)
                            <x-mary-button onclick="modalConvertConversation{{ $c->id }}.showModal()" icon="fas.crosshairs" class="btn-sm btn-square btn-success text-white" spinner />
                            <dialog id="modalConvertConversation{{ $c->id }}" class="modal">
                                <div class="modal-box text-left">
                                    <h3 class="text-lg font-bold">{{ ucfirst(__('laravel-crm::lang.convert_to_lead')) }}?</h3>
                                    <p class="py-4">{{ ucfirst(__('laravel-crm::lang.convert_to_lead_confirm')) }}</p>
                                    <div class="modal-action">
                                        <form method="dialog">
                                            <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                                            <button wire:click="convertToLead({{ $c->id }})" class="btn btn-success text-white">{{ ucfirst(__('laravel-crm::lang.convert_to_lead')) }}</button>
                                        </form>
                                    </div>
                                </div>
                            </dialog>
                        @endif
                    @endcan
                    @if($c->status !== 'closed')
                        <x-mary-button onclick="modalCloseConversation{{ $c->id }}.showModal()" icon="o-x-circle" class="btn-sm btn-square btn-warning text-white" spinner />
                        <dialog id="modalCloseConversation{{ $c->id }}" class="modal">
                            <div class="modal-box text-left">
                                <h3 class="text-lg font-bold">{{ ucfirst(__('laravel-crm::lang.close_chat')) }}?</h3>
                                <p class="py-4">{{ __('laravel-crm::lang.close_chat_confirm') }}</p>
                                <div class="modal-action">
                                    <form method="dialog">
                                        <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                                        <button wire:click="close({{ $c->id }})" class="btn btn-warning text-white">{{ ucfirst(__('laravel-crm::lang.close_chat')) }}</button>
                                    </form>
                                </div>
                            </div>
                        </dialog>
                    @endif
                    @can('delete crm chat')
                        <x-mary-button onclick="modalDeleteConversation{{ $c->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="conversation" id="{{ $c->id }}" deleting="conversation" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>

