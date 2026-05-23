<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Monitor;

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
            ['key' => 'url', 'label' => ucfirst(__('laravel-crm::lang.url'))],
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
        return view('laravel-crm::livewire.monitors.monitor-index', [
            'headers' => $this->headers(),
            'monitors' => $this->monitors(),
            'statuses' => $this->statuses(),
        ]);
    }
}
