<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.dashboard')) }}" progress-indicator>
        <x-slot:actions>
            <div>
                <select wire:model.live="period" class="select select-primary select-sm font-normal">
                    <option value="today">{{ ucfirst(__('laravel-crm::lang.today')) }}</option>
                    <option value="yesterday">{{ ucfirst(__('laravel-crm::lang.yesterday')) }}</option>
                    <option value="last_7_days">{{ __('laravel-crm::lang.last_x_days', ['days' => 7]) }}</option>
                    <option value="this_month">{{ ucfirst(__('laravel-crm::lang.this_month')) }}</option>
                    <option value="last_month">{{ ucfirst(__('laravel-crm::lang.last_month')) }}</option>
                    <option value="this_quarter">{{ ucfirst(__('laravel-crm::lang.this_quarter')) }}</option>
                    <option value="last_quarter">{{ ucfirst(__('laravel-crm::lang.last_quarter')) }}</option>
                    <option value="this_year">{{ ucfirst(__('laravel-crm::lang.this_year')) }}</option>
                    <option value="last_year">{{ ucfirst(__('laravel-crm::lang.last_year')) }}</option>
                    <option value="all_time">{{ ucfirst(__('laravel-crm::lang.all_time')) }}</option>
                </select>
            </div>
        </x-slot:actions>
    </x-mary-header>

    {{-- KPI STATS ROW 1 --}}
    <div class="grid lg:grid-cols-4 gap-4 mb-6">
        @hasleadsenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.new_leads')) }}"
                value="{{ $this->totalLeadsCount }}"
                icon="o-bolt"
                color="text-primary"
                description="{{ $this->convertedLeadsCount }} {{ __('laravel-crm::lang.converted') }} ({{ $this->conversionRate }}%)"
                class="shadow-sm"
            />
        @endhasleadsenabled

        @hasdealsenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.pipeline')) }} {{ __('laravel-crm::lang.value') }}"
                value="{{ money($this->openDealsValue, $this->getCurrency()) }}"
                icon="o-chart-bar"
                color="text-secondary"
                description="{{ $this->openDealsCount }} {{ __('laravel-crm::lang.open') }} {{ strtolower(__('laravel-crm::lang.deals')) }}"
                class="shadow-sm"
            />
        @endhasdealsenabled

        @hasdealsenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.deals')) }} {{ ucfirst(__('laravel-crm::lang.won')) }}"
                value="{{ money($this->wonDealsValue, $this->getCurrency()) }}"
                icon="o-trophy"
                color="text-success"
                description="{{ $this->wonDealsCount }} {{ strtolower(__('laravel-crm::lang.deals')) }} {{ strtolower(__('laravel-crm::lang.won')) }}"
                class="shadow-sm"
            />
        @endhasdealsenabled

        @hasinvoicesenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.outstanding')) }}"
                value="{{ money($this->invoicesOutstandingValue, $this->getCurrency()) }}"
                icon="o-clock"
                color="text-warning"
                description="{{ $this->invoicesOutstandingCount }} {{ strtolower(__('laravel-crm::lang.unpaid')) }} {{ strtolower(__('laravel-crm::lang.invoices')) }}"
                class="shadow-sm"
            />
        @endhasinvoicesenabled
    </div>

    {{-- KPI STATS ROW 2 --}}
    <div class="grid lg:grid-cols-4 gap-4 mb-6">
        @hasinvoicesenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.invoices')) }} {{ ucfirst(__('laravel-crm::lang.paid')) }}"
                value="{{ money($this->invoicesPaidValue, $this->getCurrency()) }}"
                icon="o-banknotes"
                color="text-success"
                description="{{ $this->invoicesPaidCount }} {{ strtolower(__('laravel-crm::lang.invoices')) }}"
                class="shadow-sm"
            />
        @endhasinvoicesenabled

        @hasquotesenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.quotes')) }}"
                value="{{ $this->quotesCount }}"
                icon="o-document-text"
                color="text-primary"
                description="{{ money($this->quotesValue, $this->getCurrency()) }} {{ strtolower(__('laravel-crm::lang.total')) }}"
                class="shadow-sm"
            />
        @endhasquotesenabled

        @hasordersenabled
            <x-mary-stat
                title="{{ ucfirst(__('laravel-crm::lang.orders')) }}"
                value="{{ $this->ordersCount }}"
                icon="o-shopping-cart"
                color="text-accent"
                description="{{ money($this->ordersValue, $this->getCurrency()) }} {{ strtolower(__('laravel-crm::lang.total')) }}"
                class="shadow-sm"
            />
        @endhasordersenabled

        <x-mary-stat
            title="{{ ucfirst(__('laravel-crm::lang.new')) }} {{ ucfirst(__('laravel-crm::lang.contacts')) }}"
            value="{{ $this->newPeopleCount + $this->newOrganizationsCount }}"
            icon="o-users"
            color="text-info"
            description="{{ $this->newPeopleCount }} {{ strtolower(__('laravel-crm::lang.people')) }}, {{ $this->newOrganizationsCount }} {{ strtolower(__('laravel-crm::lang.organizations')) }}"
            class="shadow-sm"
        />
    </div>

    {{-- CHARTS ROW 1: Revenue + Pipeline --}}
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        @hasinvoicesenabled
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.revenue')) }}" shadow separator>
                <div class="h-72">
                    <x-mary-chart wire:model="revenueChart" />
                </div>
            </x-mary-card>
        @endhasinvoicesenabled

        @hasdealsenabled
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.deals')) }} {{ ucfirst(__('laravel-crm::lang.pipeline')) }}" shadow separator>
                <div class="h-72">
                    <x-mary-chart wire:model="pipelineChart" />
                </div>
            </x-mary-card>
        @endhasdealsenabled
    </div>

    {{-- CHARTS ROW 2: Leads vs Deals + Deal Status --}}
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        @hasleadsenabled
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.leads')) }} vs {{ ucfirst(__('laravel-crm::lang.deals')) }}" shadow separator>
                <div class="h-72">
                    <x-mary-chart wire:model="leadsVsDealsChart" />
                </div>
            </x-mary-card>
        @endhasleadsenabled

        @hasdealsenabled
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.deal')) }} {{ ucfirst(__('laravel-crm::lang.status')) }}" shadow separator>
                <div class="h-72">
                    <x-mary-chart wire:model="dealStatusChart" />
                </div>
            </x-mary-card>
        @endhasdealsenabled
    </div>

    {{-- BOTTOM ROW: Tasks + Activity --}}
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Upcoming Tasks --}}
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.upcoming')) }} {{ ucfirst(__('laravel-crm::lang.tasks')) }}" shadow separator>
            @if($this->overdueTasksCount > 0)
                <x-mary-alert icon="o-exclamation-triangle" class="alert-warning mb-4">
                    {{ $this->overdueTasksCount }} {{ strtolower(__('laravel-crm::lang.overdue')) }} {{ strtolower(__('laravel-crm::lang.tasks')) }}
                </x-mary-alert>
            @endif

            @forelse($this->upcomingTasks as $task)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-base-200' : '' }}">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-clipboard-document-check" class="w-5 h-5 text-primary" />
                        <div>
                            <div class="font-medium text-sm">{{ $task->name }}</div>
                            @if($task->taskable)
                                <div class="text-xs text-base-content/50">
                                    {{ class_basename($task->taskable_type) }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="text-xs text-base-content/60">
                        {{ $task->due_at ? $task->due_at->diffForHumans() : '' }}
                    </div>
                </div>
            @empty
                <div class="text-base-content/50 text-sm py-4 text-center">
                    {{ ucfirst(__('laravel-crm::lang.no')) }} {{ strtolower(__('laravel-crm::lang.upcoming')) }} {{ strtolower(__('laravel-crm::lang.tasks')) }}
                </div>
            @endforelse

            @if(count($this->upcomingTasks) > 0)
                <x-slot:actions>
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.view_all')) }}" link="{{ route('laravel-crm.tasks.index') }}" icon="o-arrow-right" class="btn-ghost btn-sm" />
                </x-slot:actions>
            @endif
        </x-mary-card>

        {{-- Recent Activity --}}
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.recent')) }} {{ ucfirst(__('laravel-crm::lang.activity')) }}" shadow separator>
            @forelse($this->recentActivities as $activity)
                <div class="flex items-start gap-3 py-3 {{ !$loop->last ? 'border-b border-base-200' : '' }}">
                    @php
                        $iconMap = [
                            'created' => 'o-plus-circle',
                            'updated' => 'o-pencil',
                            'deleted' => 'o-trash',
                        ];
                        $activityIcon = $iconMap[$activity->description ?? ''] ?? 'o-information-circle';
                    @endphp
                    <x-mary-icon name="{{ $activityIcon }}" class="w-5 h-5 text-base-content/40 mt-0.5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="text-sm">
                            <span class="font-medium">
                                @if($activity->causeable)
                                    {{ $activity->causeable->name ?? 'User' }}
                                @else
                                    {{ ucfirst(__('laravel-crm::lang.system')) }}
                                @endif
                            </span>
                            <span class="text-base-content/60">
                                {{ $activity->description ?? '' }}
                            </span>
                            <span class="font-medium">
                                @if($activity->recordable)
                                    {{ class_basename($activity->recordable_type) }}
                                    @if(method_exists($activity->recordable, 'getTitle'))
                                        — {{ Str::limit($activity->recordable->getTitle(), 30) }}
                                    @elseif(isset($activity->recordable->title))
                                        — {{ Str::limit($activity->recordable->title, 30) }}
                                    @elseif(isset($activity->recordable->name))
                                        — {{ Str::limit($activity->recordable->name, 30) }}
                                    @endif
                                @endif
                            </span>
                        </div>
                        <div class="text-xs text-base-content/40 mt-0.5">
                            {{ $activity->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-base-content/50 text-sm py-4 text-center">
                    {{ ucfirst(__('laravel-crm::lang.no')) }} {{ strtolower(__('laravel-crm::lang.recent')) }} {{ strtolower(__('laravel-crm::lang.activity')) }}
                </div>
            @endforelse
        </x-mary-card>
    </div>
</div>

