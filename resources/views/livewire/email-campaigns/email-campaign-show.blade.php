<div class="crm-content">
    <x-mary-header title="{{ $campaign->name }}" subtitle="{{ $campaign->campaign_id }} — {{ ucfirst($campaign->status) }}">
        <x-slot:actions>
            @if($campaign->isCancellable())
                @can('edit crm email-campaigns')
                    <x-mary-button wire:click="cancel" wire:confirm="Cancel this campaign?" label="{{ ucfirst(__('laravel-crm::lang.cancel')) }} {{ __('laravel-crm::lang.send') }}" icon="o-x-mark" class="btn-warning text-white" />
                @endcan
            @endif
            @if($campaign->isEditable())
                @can('edit crm email-campaigns')
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.edit')) }}" link="{{ route('laravel-crm.email-campaigns.edit', $campaign) }}" icon="o-pencil-square" class="btn-primary text-white" />
                @endcan
            @endif
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }}" link="{{ route('laravel-crm.email-campaigns.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid lg:grid-cols-4 gap-5 mb-5">
        <x-mary-stat title="{{ ucfirst(__('laravel-crm::lang.total_recipients')) }}" :value="$campaign->total_recipients" icon="o-users" />
        <x-mary-stat title="{{ ucfirst(__('laravel-crm::lang.opens')) }}" :value="$campaign->unique_opens_count" :description="$campaign->openRate().'%'" icon="o-envelope-open" />
        <x-mary-stat title="{{ ucfirst(__('laravel-crm::lang.clicks')) }}" :value="$campaign->unique_clicks_count" :description="$campaign->clickRate().'%'" icon="o-cursor-arrow-rays" />
        <x-mary-stat title="{{ ucfirst(__('laravel-crm::lang.unsubscribes')) }}" :value="$campaign->unsubscribes_count" :description="$campaign->unsubscribeRate().'%'" icon="o-no-symbol" />
    </div>

    <x-mary-card shadow class="mb-5" title="{{ ucfirst(__('laravel-crm::lang.details')) }}">
        <div class="grid lg:grid-cols-2 gap-5">
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.subject')) }}</div>
                <div>{{ $campaign->subject }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.scheduled_at')) }}</div>
                <div>{{ $campaign->scheduled_at?->format('Y-m-d H:i') }} {{ $campaign->timezone }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.sent_at')) }}</div>
                <div>{{ $campaign->sent_at?->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.email_template')) }}</div>
                <div>{{ $campaign->template?->name ?? '—' }}</div>
            </div>
        </div>
    </x-mary-card>

    <x-mary-card shadow title="{{ ucfirst(__('laravel-crm::lang.recipients')) }}">
        <x-mary-table :headers="$recipientHeaders" :rows="$recipients" with-pagination>
            @scope('cell_status', $recipient)
                <x-mary-badge :value="ucfirst($recipient->status)" class="badge-neutral text-white" />
            @endscope
            @scope('cell_sent_at', $recipient)
                {{ $recipient->sent_at?->format('Y-m-d H:i') }}
            @endscope
            @scope('cell_unsubscribed_at', $recipient)
                {{ $recipient->unsubscribed_at?->format('Y-m-d H:i') }}
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
