<div class="crm-content">
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
            @scope('cell_status', $c)
                <x-mary-badge value="{{ ucfirst($c->status) }}" class="{{ $c->status === 'open' ? 'badge-success' : ($c->status === 'pending' ? 'badge-warning' : 'badge-neutral') }} text-white" />
            @endscope
            @scope('actions', $c)
                <div class="flex gap-1 justify-end">
                    @can('view crm chat')
                        <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.chat.show', $c)) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm chat')
                        <x-mary-button wire:click="delete({{ $c->id }})" icon="o-trash" class="btn-sm btn-square btn-error text-white" wire:confirm="Delete this conversation?" spinner />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>

