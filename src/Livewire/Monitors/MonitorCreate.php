<?php

namespace VentureDrake\LaravelCrm\Livewire\Monitors;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Monitors\Traits\HasMonitorCommon;

class MonitorCreate extends Component
{
    use HasMonitorCommon;

    public function mount(): void
    {
        $this->user_owner_id = auth()->user()->id ?? null;
    }

    public function save()
    {
        $validated = $this->validate();

        $monitor = $this->monitorService->create($validated);

        try {
            $monitor = $this->monitorService->create($validated);
        } catch (\Throwable $e) {
            report($e);
            $this->error(ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.could_not_be_saved').': '.$e->getMessage());

            return;
        }

        $this->success(
            ucfirst(__('laravel-crm::lang.monitor')).' '.__('laravel-crm::lang.stored'),
            redirectTo: route('laravel-crm.monitors.show', $monitor)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.monitors.monitor-create');
    }
}
