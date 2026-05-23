<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use VentureDrake\LaravelCrm\Jobs\RunMonitorCheck;
use VentureDrake\LaravelCrm\Models\Monitor;

class LaravelCrmMonitorCheck extends Command
{
    protected $signature = 'laravelcrm:monitor-check';

    protected $description = 'Dispatch RunMonitorCheck jobs for every monitor whose next check is due.';

    public function handle(): int
    {
        $now = Carbon::now();
        $dispatched = 0;
        $defaultIntervalSeconds = (int) config('laravel-crm.monitoring.default_frequency_minutes', 5) * 60;

        Monitor::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('uptime_enabled', true)
                    ->orWhere('ssl_enabled', true);
            })
            ->chunkById(100, function ($monitors) use ($now, $defaultIntervalSeconds, &$dispatched) {
                foreach ($monitors as $monitor) {
                    $intervalSeconds = (int) ($monitor->interval ?: $defaultIntervalSeconds);

                    if ($monitor->last_checked_at === null
                        || $monitor->last_checked_at->lte($now->copy()->subSeconds($intervalSeconds))
                    ) {
                        RunMonitorCheck::dispatch($monitor->id);
                        $dispatched++;
                    }
                }
            });

        $this->info("Dispatched {$dispatched} monitor check job(s).");

        return self::SUCCESS;
    }
}
