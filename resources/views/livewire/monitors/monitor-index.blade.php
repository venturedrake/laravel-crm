<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.monitors')) }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.monitors')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-select wire:model.live="status" :options="$statuses" placeholder="{{ ucfirst(__('laravel-crm::lang.status')) }}" placeholder-value="" />
            @can('create crm monitors')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create')) }}" link="{{ route('laravel-crm.monitors.create') }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$monitors" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
            @scope('cell_name', $monitor)
                {{ $monitor->displayName() }}
            @endscope
            @scope('cell_performance', $monitor)
                @php
                    $bars = (array) ($monitor->performance_bars ?? array_fill(0, 7, 0));
                    $max = max($bars) ?: 1;
                    $width = 100;
                    $height = 28;
                    $gap = 2;
                    $barWidth = ($width - ($gap * 6)) / 7;
                @endphp
                <svg viewBox="0 0 {{ $width }} {{ $height }}" width="{{ $width }}" height="{{ $height }}" aria-hidden="true" style="display:inline-block;vertical-align:middle">
                    @foreach($bars as $i => $value)
                        @php
                            $h = $value > 0 ? max(2, ($value / $max) * $height) : 1;
                            $x = $i * ($barWidth + $gap);
                            $y = $height - $h;
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $h }}" rx="1" fill="#05b3a9" />
                    @endforeach
                </svg>
            @endscope
            @scope('cell_last_status', $monitor)
                @php
                    $statusClass = match($monitor->last_status) {
                        'up' => 'badge-success',
                        'down' => 'badge-error',
                        'slow' => 'badge-warning',
                        default => 'badge-neutral',
                    };
                @endphp
                <x-mary-badge :value="ucfirst($monitor->last_status ?? '—')" class="{{ $statusClass }} text-white" />
            @endscope
            @scope('cell_last_response_time', $monitor)
                {{ $monitor->last_response_time !== null ? $monitor->last_response_time.' ms' : '—' }}
            @endscope
            @scope('cell_last_checked_at', $monitor)
                {{ $monitor->last_checked_at?->format('Y-m-d H:i') ?? '—' }}
            @endscope
            @scope('actions', $monitor)
                <div class="flex gap-1 justify-end">
                    @can('view crm monitors')
                        <x-mary-button icon="o-eye" link="{{ route('laravel-crm.monitors.show', $monitor) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('edit crm monitors')
                        <x-mary-button icon="o-pencil-square" link="{{ route('laravel-crm.monitors.edit', $monitor) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm monitors')
                        <x-mary-button onclick="modalDeleteMonitor{{ $monitor->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" />
                        <x-crm-delete-confirm model="monitor" id="{{ $monitor->id }}" deleting="monitor" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
