<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.chat_widgets')) }}" subtitle="{{ __('laravel-crm::lang.chat_widgets_subtitle') }}" progress-indicator>
        <x-slot:actions>
            @can('manage crm chat widgets')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_chat_widget')) }}" link="{{ url(route('laravel-crm.chat-widgets.create')) }}" icon="o-plus" class="btn-primary text-white" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <x-mary-table :headers="[
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'public_key', 'label' => 'Key'],
            ['key' => 'is_active', 'label' => 'Active'],
        ]" :rows="$widgets" :link="route('laravel-crm.chat-widgets.show', ['chatWidget' => '[id]'])" with-pagination>
            @scope('cell_is_active', $w)
                @if($w->is_active)
                    <x-mary-badge value="Yes" class="badge-success text-white" />
                @else
                    <x-mary-badge value="No" class="badge-neutral" />
                @endif
            @endscope
            @scope('actions', $w)
                <div class="flex gap-1 justify-end">
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.chat-widgets.show', $w)) }}" class="btn-sm btn-square btn-outline" />
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.chat-widgets.edit', $w)) }}" class="btn-sm btn-square btn-outline" />
                    <x-mary-button wire:click="delete({{ $w->id }})" icon="o-trash" class="btn-sm btn-square btn-error text-white" wire:confirm="Delete widget?" spinner />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>

