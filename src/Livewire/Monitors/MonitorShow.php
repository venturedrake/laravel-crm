<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Jobs\RunMonitorCheck;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Models\MonitorCheck;

class MonitorShow extends Component
{
    use AuthorizesRequests;
    use Toast;

    public Monitor $monitor;

    public string $chartPeriod = 'this_month';

    public array $responseTimeChart = [];

    public function mount(Monitor $monitor): void
    {
        $this->monitor = $monitor;
        $this->responseTimeChart = $this->buildResponseTimeChart();
    }

    public function updatedChartPeriod(): void
    {
        $this->responseTimeChart = $this->buildResponseTimeChart();
    }

    public function chartPeriodOptions(): array
    {
        return [
            ['id' => 'today', 'name' => ucfirst(__('laravel-crm::lang.today'))],
            ['id' => 'yesterday', 'name' => ucfirst(__('laravel-crm::lang.yesterday'))],
            ['id' => 'last_7_days', 'name' => __('laravel-crm::lang.last_x_days', ['days' => 7])],
            ['id' => 'this_month', 'name' => ucfirst(__('laravel-crm::lang.this_month'))],
            ['id' => 'last_month', 'name' => ucfirst(__('laravel-crm::lang.last_month'))],
            ['id' => 'this_quarter', 'name' => ucfirst(__('laravel-crm::lang.this_quarter'))],
            ['id' => 'last_quarter', 'name' => ucfirst(__('laravel-crm::lang.last_quarter'))],
            ['id' => 'this_year', 'name' => ucfirst(__('laravel-crm::lang.this_year'))],
            ['id' => 'last_year', 'name' => ucfirst(__('laravel-crm::lang.last_year'))],
            ['id' => 'all_time', 'name' => ucfirst(__('laravel-crm::lang.all_time'))],
        ];
    }

    protected function chartRange(): array
    {
        $now = Carbon::now();

        return match ($this->chartPeriod) {
            'today' => [today()->startOfDay(), $now, 'hour', 'H:i'],
            'yesterday' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay(), 'hour', 'H:i'],
            'last_7_days' => [today()->subDays(6)->startOfDay(), $now, 'day', 'M j'],
            'last_month' => [today()->subMonth()->startOfMonth()->startOfDay(), today()->subMonth()->endOfMonth()->endOfDay(), 'day', 'M j'],
            'this_quarter' => [today()->startOfQuarter()->startOfDay(), $now, 'week', 'M j'],
            'last_quarter' => [today()->subQuarter()->startOfQuarter()->startOfDay(), today()->subQuarter()->endOfQuarter()->endOfDay(), 'week', 'M j'],
            'this_year' => [today()->startOfYear()->startOfDay(), $now, 'month', 'M Y'],
            'last_year' => [today()->subYear()->startOfYear()->startOfDay(), today()->subYear()->endOfYear()->endOfDay(), 'month', 'M Y'],
            'all_time' => [$this->allTimeStart($now), $now, 'month', 'M Y'],
            default => [today()->startOfMonth()->startOfDay(), $now, 'day', 'M j'],
        };
    }

    private function allTimeStart(Carbon $now): Carbon
    {
        $earliest = MonitorCheck::where('monitor_id', $this->monitor->id)
            ->where('type', 'uptime')
            ->min('checked_at');

        return $earliest ? Carbon::parse($earliest) : $now->copy()->subMonth();
    }

    protected function buildResponseTimeChart(): array
    {
        [$start, $end, $bucket, $format] = $this->chartRange();

        $checks = MonitorCheck::where('monitor_id', $this->monitor->id)
            ->where('type', 'uptime')
            ->whereBetween('checked_at', [$start, $end])
            ->whereNotNull('response_time')
            ->orderBy('checked_at')
            ->get(['response_time', 'checked_at']);

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $buckets[$cursor->copy()->getTimestamp()] = ['label' => $cursor->format($format), 'values' => []];
            $cursor = $this->advanceCursor($cursor, $bucket);
        }

        foreach ($checks as $check) {
            $key = $this->bucketKey($check->checked_at, $start, $bucket);

            if ($key !== null && isset($buckets[$key])) {
                $buckets[$key]['values'][] = (int) $check->response_time;
            }
        }

        $labels = [];
        $data = [];

        foreach ($buckets as $b) {
            $labels[] = $b['label'];
            $data[] = $b['values'] === [] ? 0 : (int) round(array_sum($b['values']) / count($b['values']));
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ucfirst(__('laravel-crm::lang.average_response_time')).' (ms)',
                        'data' => $data,
                        'backgroundColor' => '#05b3a9',
                        'borderColor' => '#05b3a9',
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
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    private function advanceCursor(Carbon $cursor, string $bucket): Carbon
    {
        return match ($bucket) {
            'hour' => $cursor->copy()->addHour(),
            'day' => $cursor->copy()->addDay(),
            'week' => $cursor->copy()->addWeek(),
            'month' => $cursor->copy()->addMonth(),
            default => $cursor->copy()->addDay(),
        };
    }

    private function bucketKey(Carbon $when, Carbon $start, string $bucket): ?int
    {
        if ($when->lt($start)) {
            return null;
        }

        if ($bucket === 'month') {
            $monthsDiff = ($when->year - $start->year) * 12 + ($when->month - $start->month);

            return $start->copy()->addMonths($monthsDiff)->getTimestamp();
        }

        $sizeSeconds = match ($bucket) {
            'hour' => 3600,
            'day' => 86400,
            'week' => 7 * 86400,
            default => 86400,
        };

        $delta = $when->getTimestamp() - $start->getTimestamp();
        $index = (int) floor($delta / $sizeSeconds);

        return $start->copy()->addSeconds($index * $sizeSeconds)->getTimestamp();
    }

    public function recentChecks()
    {
        return $this->monitor->checks()
            ->orderBy('checked_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function checkHeaders(): array
    {
        return [
            ['key' => 'type', 'label' => ucfirst(__('laravel-crm::lang.type'))],
            ['key' => 'status', 'label' => ucfirst(__('laravel-crm::lang.status'))],
            ['key' => 'status_code', 'label' => ucfirst(__('laravel-crm::lang.status_code'))],
            ['key' => 'response_time', 'label' => ucfirst(__('laravel-crm::lang.response_time'))],
            ['key' => 'error_message', 'label' => ucfirst(__('laravel-crm::lang.error'))],
            ['key' => 'checked_at', 'label' => ucfirst(__('laravel-crm::lang.checked_at'))],
        ];
    }

    public function runCheck(): void
    {
        $this->authorize('update', $this->monitor);

        try {
            RunMonitorCheck::dispatchSync($this->monitor->id);
        } catch (\Throwable $e) {
            $this->error(ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.check').' '.__('laravel-crm::lang.failed'));

            return;
        }

        $this->monitor->refresh();

        $this->success(ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.check').' '.__('laravel-crm::lang.completed'));
    }

    public function delete($id): void
    {
        if ($monitor = Monitor::find($id)) {
            $this->authorize('delete', $monitor);
            $monitor->delete();
            $this->success(
                ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.deleted'),
                redirectTo: route('laravel-crm.monitors.index')
            );
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.monitors.monitor-show', [
            'checks' => $this->recentChecks(),
            'checkHeaders' => $this->checkHeaders(),
        ]);
    }
}
