<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Task;

class Dashboard extends Component
{
    public string $period = 'this_month';

    // Chart data properties for MaryUI <x-mary-chart>
    public array $revenueChart = [];

    public array $pipelineChart = [];

    public array $leadsVsDealsChart = [];

    public array $dealStatusChart = [];

    public function updatedPeriod(): void
    {
        $this->loadChartData();
    }

    public function mount(): void
    {
        $this->loadChartData();
    }

    protected function loadChartData(): void
    {
        $this->revenueChart = $this->buildRevenueChart();
        $this->pipelineChart = $this->buildPipelineChart();
        $this->leadsVsDealsChart = $this->buildLeadsVsDealsChart();
        $this->dealStatusChart = $this->buildDealStatusChart();
    }

    protected function getDateRange(): array
    {
        return match ($this->period) {
            'today' => [today()->startOfDay(), today()->endOfDay()],
            'yesterday' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()],
            'last_7_days' => [today()->subDays(6)->startOfDay(), now()],
            'this_month' => [today()->startOfMonth()->startOfDay(), now()],
            'last_month' => [today()->subMonth()->startOfMonth()->startOfDay(), today()->subMonth()->endOfMonth()->endOfDay()],
            'this_quarter' => [today()->startOfQuarter()->startOfDay(), now()],
            'last_quarter' => [today()->subQuarter()->startOfQuarter()->startOfDay(), today()->subQuarter()->endOfQuarter()->endOfDay()],
            'this_year' => [today()->startOfYear()->startOfDay(), now()],
            'last_year' => [today()->subYear()->startOfYear()->startOfDay(), today()->subYear()->endOfYear()->endOfDay()],
            'all_time' => [Carbon::parse('2000-01-01'), now()],
            default => [today()->startOfMonth()->startOfDay(), now()],
        };
    }

    public function getCurrency(): string
    {
        return app('laravel-crm.settings')->get('currency') ?? 'USD';
    }

    protected function formatMoney(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2);
    }

    // --- Stat computations ---

    public function getOpenLeadsCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Lead::whereNull('converted_at')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    public function getOpenLeadsValueProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return (int) Lead::whereNull('converted_at')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    public function getConvertedLeadsCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Lead::whereNotNull('converted_at')
            ->whereBetween('converted_at', [$start, $end])
            ->count();
    }

    public function getTotalLeadsCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Lead::whereBetween('created_at', [$start, $end])->count();
    }

    public function getConversionRateProperty(): string
    {
        $total = $this->totalLeadsCount;
        if ($total === 0) {
            return '0';
        }

        return number_format(($this->convertedLeadsCount / $total) * 100, 1);
    }

    public function getOpenDealsCountProperty(): int
    {
        return Deal::whereNull('closed_status')->count();
    }

    public function getOpenDealsValueProperty(): int
    {
        return (int) Deal::whereNull('closed_status')->sum('amount');
    }

    public function getWonDealsCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Deal::where('closed_status', 'won')
            ->whereBetween('closed_at', [$start, $end])
            ->count();
    }

    public function getWonDealsValueProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return (int) Deal::where('closed_status', 'won')
            ->whereBetween('closed_at', [$start, $end])
            ->sum('amount');
    }

    public function getLostDealsCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Deal::where('closed_status', 'lost')
            ->whereBetween('closed_at', [$start, $end])
            ->count();
    }

    public function getQuotesCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Quote::whereBetween('created_at', [$start, $end])->count();
    }

    public function getQuotesValueProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return (int) Quote::whereBetween('created_at', [$start, $end])->sum('total');
    }

    public function getOrdersCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Order::whereBetween('created_at', [$start, $end])->count();
    }

    public function getOrdersValueProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return (int) Order::whereBetween('created_at', [$start, $end])->sum('total');
    }

    public function getInvoicesOutstandingCountProperty(): int
    {
        return Invoice::whereNull('fully_paid_at')->count();
    }

    public function getInvoicesOutstandingValueProperty(): int
    {
        return (int) Invoice::whereNull('fully_paid_at')
            ->where('amount_due', '>', 0)
            ->sum('amount_due');
    }

    public function getInvoicesPaidCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Invoice::whereNotNull('fully_paid_at')
            ->whereBetween('fully_paid_at', [$start, $end])
            ->count();
    }

    public function getInvoicesPaidValueProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return (int) Invoice::whereNotNull('fully_paid_at')
            ->whereBetween('fully_paid_at', [$start, $end])
            ->sum('total');
    }

    public function getNewPeopleCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Person::whereBetween('created_at', [$start, $end])->count();
    }

    public function getNewOrganizationsCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Organization::whereBetween('created_at', [$start, $end])->count();
    }

    public function getDeliveriesCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return Delivery::whereBetween('created_at', [$start, $end])->count();
    }

    public function getPurchaseOrdersCountProperty(): int
    {
        [$start, $end] = $this->getDateRange();

        return PurchaseOrder::whereBetween('created_at', [$start, $end])->count();
    }

    // --- Upcoming Tasks ---

    public function getUpcomingTasksProperty()
    {
        return Task::whereNull('completed_at')
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->limit(5)
            ->get();
    }

    public function getOverdueTasksCountProperty(): int
    {
        return Task::whereNull('completed_at')
            ->where('due_at', '<', now())
            ->count();
    }

    // --- Recent Activity ---

    public function getRecentActivitiesProperty()
    {
        return Activity::latest()
            ->limit(8)
            ->get();
    }

    // --- Chart builders ---

    protected function getGranularity(): string
    {
        [$start, $end] = $this->getDateRange();
        $days = $start->diffInDays($end);

        if ($days <= 1) {
            return 'hour';
        } elseif ($days <= 31) {
            return 'day';
        } elseif ($days <= 120) {
            return 'week';
        }

        return 'month';
    }

    protected function getDateLabels(): array
    {
        [$start, $end] = $this->getDateRange();
        $granularity = $this->getGranularity();

        $labels = [];

        if ($granularity === 'hour') {
            $current = $start->copy();
            while ($current <= $end) {
                $labels[] = $current->format('H:i');
                $current->addHour();
            }
        } elseif ($granularity === 'day') {
            $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());
            foreach ($period as $date) {
                $labels[] = $date->format('M d');
            }
        } elseif ($granularity === 'week') {
            $current = $start->copy()->startOfWeek();
            while ($current <= $end) {
                $labels[] = 'W'.$current->weekOfYear.' '.$current->format('M d');
                $current->addWeek();
            }
        } else {
            $current = $start->copy()->startOfMonth();
            while ($current <= $end) {
                $labels[] = $current->format('M Y');
                $current->addMonth();
            }
        }

        return $labels;
    }

    protected function groupByGranularity($query, string $dateColumn = 'created_at', string $aggregate = 'count', ?string $sumColumn = null): array
    {
        [$start, $end] = $this->getDateRange();
        $granularity = $this->getGranularity();
        $labels = $this->getDateLabels();

        $prefix = config('laravel-crm.db_table_prefix');

        if ($granularity === 'hour') {
            $format = '%H:00';
            $phpFormat = 'H:00';
        } elseif ($granularity === 'day') {
            $format = '%b %d';
            $phpFormat = 'M d';
        } elseif ($granularity === 'week') {
            // We'll use PHP-side grouping for weeks
            $format = null;
            $phpFormat = null;
        } else {
            $format = '%b %Y';
            $phpFormat = 'M Y';
        }

        // Use PHP-side grouping for reliable results across MySQL versions
        $records = $query->whereBetween($dateColumn, [$start, $end])
            ->orderBy($dateColumn)
            ->get();

        $grouped = [];
        foreach ($labels as $label) {
            $grouped[$label] = 0;
        }

        foreach ($records as $record) {
            $date = Carbon::parse($record->$dateColumn);

            if ($granularity === 'hour') {
                $key = $date->format('H').':00';
            } elseif ($granularity === 'day') {
                $key = $date->format('M d');
            } elseif ($granularity === 'week') {
                $key = 'W'.$date->weekOfYear.' '.$date->copy()->startOfWeek()->format('M d');
            } else {
                $key = $date->format('M Y');
            }

            if (isset($grouped[$key])) {
                if ($aggregate === 'sum' && $sumColumn) {
                    $grouped[$key] += (int) $record->$sumColumn;
                } else {
                    $grouped[$key]++;
                }
            }
        }

        return array_values($grouped);
    }

    protected function buildRevenueChart(): array
    {
        $labels = $this->getDateLabels();

        $invoicePaidData = $this->groupByGranularity(
            Invoice::whereNotNull('fully_paid_at')->select('*'),
            'fully_paid_at',
            'sum',
            'total'
        );

        // Convert from cents to dollars
        $invoicePaidData = array_map(fn ($v) => round($v / 100, 2), $invoicePaidData);

        $orderData = $this->groupByGranularity(
            Order::select('*'),
            'created_at',
            'sum',
            'total'
        );
        $orderData = array_map(fn ($v) => round($v / 100, 2), $orderData);

        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ucfirst(__('laravel-crm::lang.invoices')).' ('.__('laravel-crm::lang.paid').')',
                        'data' => $invoicePaidData,
                        'borderColor' => '#05b3a9',
                        'backgroundColor' => 'rgba(5, 179, 169, 0.1)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                    [
                        'label' => ucfirst(__('laravel-crm::lang.orders')),
                        'data' => $orderData,
                        'borderColor' => '#6505B3',
                        'backgroundColor' => 'rgba(101, 5, 179, 0.1)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => '__CURRENCY_PREFIX__',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildPipelineChart(): array
    {
        $stages = PipelineStage::orderBy('order', 'asc')->get();

        $labels = [];
        $values = [];
        $colors = [
            '#05b3a9', '#6505B3', '#B34105', '#2563eb', '#d946ef',
            '#f59e0b', '#10b981', '#6366f1', '#ef4444', '#8b5cf6',
        ];

        foreach ($stages as $i => $stage) {
            $labels[] = $stage->name;
            $sum = Deal::where('pipeline_stage_id', $stage->id)
                ->whereNull('closed_status')
                ->sum('amount');
            $values[] = round($sum / 100, 2);
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ucfirst(__('laravel-crm::lang.deals')).' '.__('laravel-crm::lang.value'),
                        'data' => $values,
                        'backgroundColor' => array_slice($colors, 0, count($labels)),
                        'borderRadius' => 4,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    protected function buildLeadsVsDealsChart(): array
    {
        $labels = $this->getDateLabels();

        $leadsData = $this->groupByGranularity(Lead::select('*'), 'created_at', 'count');
        $dealsData = $this->groupByGranularity(Deal::select('*'), 'created_at', 'count');

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ucfirst(__('laravel-crm::lang.leads')),
                        'data' => $leadsData,
                        'backgroundColor' => 'rgba(5, 179, 169, 0.7)',
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => ucfirst(__('laravel-crm::lang.deals')),
                        'data' => $dealsData,
                        'backgroundColor' => 'rgba(101, 5, 179, 0.7)',
                        'borderRadius' => 4,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['stepSize' => 1],
                    ],
                ],
            ],
        ];
    }

    protected function buildDealStatusChart(): array
    {
        [$start, $end] = $this->getDateRange();

        $open = Deal::whereNull('closed_status')->count();
        $won = Deal::where('closed_status', 'won')->whereBetween('closed_at', [$start, $end])->count();
        $lost = Deal::where('closed_status', 'lost')->whereBetween('closed_at', [$start, $end])->count();

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => [
                    ucfirst(__('laravel-crm::lang.open')),
                    ucfirst(__('laravel-crm::lang.won')),
                    ucfirst(__('laravel-crm::lang.lost')),
                ],
                'datasets' => [
                    [
                        'data' => [$open, $won, $lost],
                        'backgroundColor' => ['#2563eb', '#10b981', '#ef4444'],
                        'hoverOffset' => 4,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('laravel-crm::livewire.dashboard');
    }
}
