<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.email_campaigns')) }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.email_campaigns')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-select wire:model.live="status" :options="$statuses" placeholder="{{ ucfirst(__('laravel-crm::lang.status')) }}" placeholder-value="" />
            @can('create crm email-campaigns')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create')) }}" link="{{ route('laravel-crm.email-campaigns.create') }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$campaigns" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_status', $campaign)
                <x-mary-badge :value="ucfirst($campaign->status)" class="badge-neutral text-white" />
            @endscope
            @scope('cell_scheduled_at', $campaign)
                {{ $campaign->scheduled_at?->format('Y-m-d H:i') }}
            @endscope
            @scope('cell_sent_at', $campaign)
                {{ $campaign->sent_at?->format('Y-m-d H:i') }}
            @endscope
            @scope('actions', $campaign)
                <div class="flex gap-1 justify-end">
                    @can('view crm email-campaigns')
                        <x-mary-button icon="o-eye" link="{{ route('laravel-crm.email-campaigns.show', $campaign) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('edit crm email-campaigns')
                        @if($campaign->isEditable())
                            <x-mary-button icon="o-pencil-square" link="{{ route('laravel-crm.email-campaigns.edit', $campaign) }}" class="btn-sm btn-square btn-outline" />
                        @endif
                    @endcan
                    @can('delete crm email-campaigns')
                        <x-mary-button wire:click="delete({{ $campaign->id }})" wire:confirm="Delete this campaign?" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
