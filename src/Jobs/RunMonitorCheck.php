<?php

namespace VentureDrake\LaravelCrm\Jobs;

use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Models\MonitorCheck;
use VentureDrake\LaravelCrm\Notifications\MonitorDownNotification;
use VentureDrake\LaravelCrm\Notifications\MonitorPerformanceNotification;
use VentureDrake\LaravelCrm\Notifications\MonitorRecoveredNotification;
use VentureDrake\LaravelCrm\Notifications\MonitorSslExpiringNotification;
use VentureDrake\LaravelCrm\Notifications\MonitorSslInvalidNotification;
use VentureDrake\LaravelCrm\Services\MonitorCheckService;

class RunMonitorCheck implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $monitorId;

    public function __construct(int $monitorId)
    {
        $this->monitorId = $monitorId;
    }

    public function handle(MonitorCheckService $service): void
    {
        $monitor = Monitor::find($this->monitorId);

        if (! $monitor) {
            return;
        }

        if ($monitor->uptime_enabled) {
            $this->runUptime($monitor, $service);
        }

        if ($monitor->ssl_enabled && $this->sslIsDue($monitor)) {
            $this->runSsl($monitor, $service);
        }
    }

    public function failed(Throwable $e): void
    {
        MonitorCheck::create([
            'monitor_id' => $this->monitorId,
            'type' => 'uptime',
            'status' => 'error',
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
            'checked_at' => Carbon::now(),
        ]);
    }

    private function runUptime(Monitor $monitor, MonitorCheckService $service): void
    {
        $result = $service->checkUptime($monitor);
        $now = Carbon::now();
        $previousStatus = $monitor->last_status;

        MonitorCheck::create([
            'monitor_id' => $monitor->id,
            'type' => 'uptime',
            'status' => $result['status'],
            'status_code' => $result['status_code'],
            'response_time' => $result['response_time_ms'],
            'error_message' => $result['error'],
            'checked_at' => $now,
        ]);

        $monitor->last_status = $result['status'];
        $monitor->last_response_time = $result['response_time_ms'];
        $monitor->last_status_code = $result['status_code'];
        $monitor->last_checked_at = $now;

        if ($previousStatus !== $result['status']) {
            $monitor->last_status_changed_at = $now;
        }

        $this->evaluateUptimeAlerts($monitor, $result, $previousStatus, $now);

        $monitor->save();
    }

    private function runSsl(Monitor $monitor, MonitorCheckService $service): void
    {
        $result = $service->checkSsl($monitor);
        $now = Carbon::now();

        $status = $result['valid'] ? 'valid' : 'invalid';

        MonitorCheck::create([
            'monitor_id' => $monitor->id,
            'type' => 'ssl',
            'status' => $status,
            'error_message' => $result['error'],
            'ssl_expires_at' => $result['expires_at'],
            'checked_at' => $now,
        ]);

        $monitor->ssl_last_checked_at = $now;
        $monitor->ssl_status = $status;
        $monitor->ssl_issuer = $result['issuer'];
        $monitor->ssl_expires_at = $result['expires_at'];

        $this->evaluateSslAlerts($monitor, $result, $now);

        $monitor->save();
    }

    private function sslIsDue(Monitor $monitor): bool
    {
        if ($monitor->ssl_last_checked_at === null) {
            return true;
        }

        $recheckHours = (int) config('laravel-crm.monitoring.ssl_recheck_hours', 12);

        return $monitor->ssl_last_checked_at->lt(Carbon::now()->subHours($recheckHours));
    }

    private function evaluateUptimeAlerts(Monitor $monitor, array $result, ?string $previousStatus, Carbon $now): void
    {
        $status = $result['status'];

        if ($status === 'down') {
            if ($monitor->down_since_at === null) {
                $monitor->down_since_at = $now;
            }

            $debounceMinutes = (int) ($monitor->downtime_minutes_before_alert
                ?? config('laravel-crm.monitoring.down_debounce_minutes', 2));

            if ($monitor->notified_at === null && $monitor->down_since_at->lte($now->copy()->subMinutes($debounceMinutes))) {
                $this->notifyRecipients($monitor, fn ($owner) => new MonitorDownNotification($monitor, $owner, $result));
                $monitor->notified_at = $now;
            }

            return;
        }

        if ($status === 'slow') {
            $rateLimitMinutes = (int) config('laravel-crm.monitoring.perf_alert_rate_limit_minutes', 60);

            if ($monitor->notified_at === null || $monitor->notified_at->lte($now->copy()->subMinutes($rateLimitMinutes))) {
                $this->notifyRecipients($monitor, fn ($owner) => new MonitorPerformanceNotification($monitor, $owner, $result));
                $monitor->notified_at = $now;
            }

            return;
        }

        if ($monitor->down_since_at !== null || $monitor->notified_at !== null) {
            if ($previousStatus === 'down' || $previousStatus === 'slow') {
                $this->notifyRecipients($monitor, fn ($owner) => new MonitorRecoveredNotification($monitor, $owner, $result));
            }

            $monitor->down_since_at = null;
            $monitor->notified_at = null;
        }
    }

    private function evaluateSslAlerts(Monitor $monitor, array $result, Carbon $now): void
    {
        $warningDays = (int) config('laravel-crm.monitoring.ssl_expiry_warning_days', 14);
        $rateLimitHours = (int) config('laravel-crm.monitoring.ssl_alert_rate_limit_hours', 24);

        $isExpiringSoon = $result['expires_at'] instanceof Carbon
            && $result['expires_at']->lte($now->copy()->addDays($warningDays));

        $hasIssue = ! $result['valid'] || $isExpiringSoon;

        if (! $hasIssue) {
            $monitor->ssl_notified_at = null;

            return;
        }

        $canNotify = $monitor->ssl_notified_at === null
            || $monitor->ssl_notified_at->lte($now->copy()->subHours($rateLimitHours));

        if (! $canNotify) {
            return;
        }

        if (! $result['valid']) {
            $this->notifyRecipients($monitor, fn ($owner) => new MonitorSslInvalidNotification($monitor, $owner, $result));
        } else {
            $this->notifyRecipients($monitor, fn ($owner) => new MonitorSslExpiringNotification($monitor, $owner, $result));
        }

        $monitor->ssl_notified_at = $now;
    }

    private function notifyRecipients(Monitor $monitor, callable $factory): void
    {
        $userId = $monitor->user_assigned_id ?: $monitor->user_owner_id;

        if (! $userId) {
            return;
        }

        $user = User::find($userId);

        if (! $user) {
            return;
        }

        Notification::send($user, $factory($user));
    }
}
