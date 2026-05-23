<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Jobs\RunMonitorCheck;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorShow extends Component
{
    use AuthorizesRequests;
    use Toast;

    public Monitor $monitor;

    public function mount(Monitor $monitor): void
    {
        $this->monitor = $monitor;
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
