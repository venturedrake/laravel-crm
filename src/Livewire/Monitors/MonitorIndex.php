<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Models\MonitorCheck;

class MonitorIndex extends Component
{
    use AuthorizesRequests;
    use Toast;
    use WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $status = '';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function statuses(): array
    {
        return [
            ['id' => 'up', 'name' => ucfirst(__('laravel-crm::lang.monitor_status_up'))],
            ['id' => 'down', 'name' => ucfirst(__('laravel-crm::lang.monitor_status_down'))],
            ['id' => 'slow', 'name' => ucfirst(__('laravel-crm::lang.monitor_status_slow'))],
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'monitor_id', 'label' => ucfirst(__('laravel-crm::lang.number'))],
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'performance', 'label' => ucfirst(__('laravel-crm::lang.performance')), 'sortable' => false],
            ['key' => 'last_status', 'label' => ucfirst(__('laravel-crm::lang.status'))],
            ['key' => 'last_response_time', 'label' => ucfirst(__('laravel-crm::lang.response_time'))],
            ['key' => 'last_checked_at', 'label' => ucfirst(__('laravel-crm::lang.last_checked'))],
        ];
    }

    public function monitors(): LengthAwarePaginator
    {
        return Monitor::query()
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', "%$this->search%")
                        ->orWhere('url', 'like', "%$this->search%")
                        ->orWhere('monitor_id', 'like', "%$this->search%");
                });
            })
            ->when($this->status, fn (Builder $q) => $q->where('last_status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    /**
     * Bulk-load the last 7 days of uptime checks for the given monitors and
     * return an array keyed by monitor id where each value is an array of
     * 7 integers (one per day, oldest -> newest) representing the average
     * response time in ms (0 when no data).
     */
    public function performanceData(Collection $monitors): array
    {
        if ($monitors->isEmpty()) {
            return [];
        }

        $start = Carbon::now()->subDays(6)->startOfDay();
        $ids = $monitors->pluck('id')->all();

        $rows = MonitorCheck::query()
            ->whereIn('monitor_id', $ids)
            ->where('type', 'uptime')
            ->whereNotNull('response_time')
            ->where('checked_at', '>=', $start)
            ->get(['monitor_id', 'response_time', 'checked_at']);

        $byMonitor = [];

        foreach ($ids as $id) {
            $byMonitor[$id] = array_fill(0, 7, ['sum' => 0, 'count' => 0]);
        }

        foreach ($rows as $row) {
            $dayIndex = (int) floor(($row->checked_at->getTimestamp() - $start->getTimestamp()) / 86400);

            if ($dayIndex < 0 || $dayIndex > 6) {
                continue;
            }

            $byMonitor[$row->monitor_id][$dayIndex]['sum'] += (int) $row->response_time;
            $byMonitor[$row->monitor_id][$dayIndex]['count']++;
        }

        $result = [];

        foreach ($byMonitor as $id => $buckets) {
            $result[$id] = array_map(
                fn ($b) => $b['count'] === 0 ? 0 : (int) round($b['sum'] / $b['count']),
                $buckets,
            );
        }

        return $result;
    }

    public function delete($id): void
    {
        if ($monitor = Monitor::find($id)) {
            $this->authorize('delete', $monitor);
            $monitor->delete();
            $this->success(ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.deleted'));
        }
    }

    public function render()
    {
        $monitors = $this->monitors();

        return view('laravel-crm::livewire.monitors.monitor-index', [
            'headers' => $this->headers(),
            'monitors' => $monitors,
            'statuses' => $this->statuses(),
            'performance' => $this->performanceData($monitors->getCollection()),
        ]);
    }
}
