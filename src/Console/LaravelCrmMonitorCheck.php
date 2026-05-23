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

        Monitor::query()
            ->where(function ($query) {
                $query->where('uptime_enabled', true)
                    ->orWhere('ssl_enabled', true);
            })
            ->chunkById(100, function ($monitors) use ($now, &$dispatched) {
                foreach ($monitors as $monitor) {
                    if ($monitor->last_checked_at === null
                        || $monitor->last_checked_at->lte($now->copy()->subMinutes((int) ($monitor->frequency_minutes ?? 0)))
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
