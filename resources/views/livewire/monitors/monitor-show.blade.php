<div class="crm-content">
    @php
        $statusClass = match($monitor->last_status) {
            'up' => 'badge-success',
            'down' => 'badge-error',
            'slow' => 'badge-warning',
            default => 'badge-neutral',
        };
    @endphp

    <x-mary-header title="{{ $monitor->displayName() }}" subtitle="{{ $monitor->monitor_id }} — {{ $monitor->url }}">
        <x-slot:actions>
            @can('edit crm monitors')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.run_check_now')) }}" wire:click="runCheck" spinner="runCheck" icon="o-arrow-path" class="btn-primary text-white" />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.edit')) }}" link="{{ route('laravel-crm.monitors.edit', $monitor) }}" icon="o-pencil-square" class="btn-outline" />
            @endcan
            @can('delete crm monitors')
                <x-mary-button onclick="modalDeleteMonitor{{ $monitor->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" />
                <x-crm-delete-confirm model="monitor" id="{{ $monitor->id }}" deleting="monitor" />
            @endcan
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }}" link="{{ route('laravel-crm.monitors.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid lg:grid-cols-4 gap-5 mb-5">
        <x-mary-card shadow>
            <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.status')) }}</div>
            <x-mary-badge :value="ucfirst($monitor->last_status ?? '—')" class="{{ $statusClass }} text-white mt-2" />
        </x-mary-card>
        <x-mary-card shadow>
            <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.response_time')) }}</div>
            <div class="text-lg font-semibold">{{ $monitor->last_response_time !== null ? $monitor->last_response_time.' ms' : '—' }}</div>
        </x-mary-card>
        <x-mary-card shadow>
            <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.status_code')) }}</div>
            <div class="text-lg font-semibold">{{ $monitor->last_status_code ?? '—' }}</div>
        </x-mary-card>
        <x-mary-card shadow>
            <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.last_checked')) }}</div>
            <div class="text-lg font-semibold">{{ $monitor->last_checked_at?->format('Y-m-d H:i') ?? '—' }}</div>
        </x-mary-card>
    </div>

    <x-mary-card shadow class="mb-5" title="{{ ucfirst(__('laravel-crm::lang.average_response_time')) }}">
        <x-slot:menu>
            <select wire:model.live="chartPeriod" class="select select-primary select-sm font-normal">
                @foreach($this->chartPeriodOptions() as $option)
                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                @endforeach
            </select>
        </x-slot:menu>
        <div class="h-72">
            <x-mary-chart wire:model="responseTimeChart" class="!h-full" />
        </div>
    </x-mary-card>

    <x-mary-card shadow class="mb-5" title="{{ ucfirst(__('laravel-crm::lang.details')) }}">
        <div class="grid lg:grid-cols-2 gap-5">
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.type')) }}</div>
                <div>{{ strtoupper($monitor->type ?? '') }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.method')) }}</div>
                <div>{{ $monitor->method }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.expected_status_code')) }}</div>
                <div>{{ $monitor->expected_status_code ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.run_check_every')) }}</div>
                <div>{{ $monitor->interval }} {{ __('laravel-crm::lang.minutes') }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.minutes_downtime_before_notification')) }}</div>
                <div>{{ $monitor->downtime_minutes_before_alert ?? '—' }} {{ __('laravel-crm::lang.minutes') }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.monitor_performance_threshold')) }}</div>
                <div>{{ $monitor->perf_threshold_ms ?? '—' }} {{ __('laravel-crm::lang.ms') }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.owner')) }}</div>
                <div>{{ $monitor->ownerUser?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.active')) }}</div>
                <div>{{ $monitor->is_active ? __('laravel-crm::lang.yes') : __('laravel-crm::lang.no') }}</div>
            </div>
        </div>
    </x-mary-card>

    <x-mary-card shadow title="{{ ucfirst(__('laravel-crm::lang.recent_checks')) }}">
        <x-mary-table :headers="$checkHeaders" :rows="$checks">
            @scope('cell_status', $check)
                @php
                    $rowClass = match($check->status) {
                        'up' => 'badge-success',
                        'down', 'error' => 'badge-error',
                        'slow' => 'badge-warning',
                        default => 'badge-neutral',
                    };
                @endphp
                <x-mary-badge :value="ucfirst($check->status ?? '—')" class="{{ $rowClass }} text-white" />
            @endscope
            @scope('cell_response_time', $check)
                {{ $check->response_time !== null ? $check->response_time.' ms' : '—' }}
            @endscope
            @scope('cell_checked_at', $check)
                {{ $check->checked_at?->format('Y-m-d H:i:s') }}
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
