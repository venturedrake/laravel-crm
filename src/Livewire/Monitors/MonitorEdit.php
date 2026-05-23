<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Monitors\Traits\HasMonitorCommon;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorEdit extends Component
{
    use HasMonitorCommon;

    public Monitor $monitor;

    public function mount(Monitor $monitor): void
    {
        $this->monitor = $monitor;
        $this->name = $monitor->name;
        $this->description = $monitor->description;
        $this->type = $monitor->type ?? 'https';
        $this->url = $monitor->url;
        $this->method = $monitor->method ?? 'GET';
        $this->expected_status_code = $monitor->expected_status_code ?? 200;
        $this->interval = $monitor->interval ?? 5;
        $this->downtime_minutes_before_alert = $monitor->downtime_minutes_before_alert ?? 5;
        $this->perf_threshold_ms = $monitor->perf_threshold_ms ?? 3500;
        $this->is_active = (bool) $monitor->is_active;
        $this->user_owner_id = $monitor->user_owner_id;
    }

    public function save()
    {
        $validated = $this->validate();

        try {
            $this->monitorService->update($this->monitor, $validated);
        } catch (\Throwable $e) {
            $this->error(ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.could_not_be_saved'));

            return;
        }

        $this->success(
            ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.updated'),
            redirectTo: route('laravel-crm.monitors.show', $this->monitor)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.monitors.monitor-edit');
    }
}
