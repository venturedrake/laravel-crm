<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.sms_campaigns')) }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.sms_campaigns')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-select wire:model.live="status" :options="$statuses" placeholder="{{ ucfirst(__('laravel-crm::lang.status')) }}" placeholder-value="" />
            @can('create crm sms-campaigns')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create')) }}" link="{{ route('laravel-crm.sms-campaigns.create') }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    @if(! $clickSendConfigured)
        <div role="alert" class="alert alert-warning mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>
                {{ __('laravel-crm::lang.clicksend_not_configured') }}
                @can('edit crm settings')
                    <a href="{{ route('laravel-crm.integrations.clicksend') }}" class="link font-semibold">{{ ucfirst(__('laravel-crm::lang.integrations')) }}</a>.
                @endcan
            </span>
        </div>
    @endif

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
                    @can('view crm sms-campaigns')
                        <x-mary-button icon="o-eye" link="{{ route('laravel-crm.sms-campaigns.show', $campaign) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('edit crm sms-campaigns')
                        @if($campaign->isEditable())
                            <x-mary-button icon="o-pencil-square" link="{{ route('laravel-crm.sms-campaigns.edit', $campaign) }}" class="btn-sm btn-square btn-outline" />
                        @endif
                    @endcan
                    @can('delete crm sms-campaigns')
                        <x-mary-button onclick="modalDeleteSmsCampaign{{ $campaign->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="smsCampaign" id="{{ $campaign->id }}" deleting="sms campaign" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
