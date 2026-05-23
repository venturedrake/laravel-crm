<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorPerformanceNotification extends Notification
{
    use Queueable;

    protected Monitor $monitor;

    protected ?int $responseTimeMs;

    public function __construct(Monitor $monitor, ?int $responseTimeMs = null)
    {
        $this->monitor = $monitor;
        $this->responseTimeMs = $responseTimeMs;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Monitor slow: '.$this->monitor->name)
            ->line($this->monitor->name.' ('.$this->monitor->url.') is responding slowly.');

        if ($this->responseTimeMs !== null) {
            $mail->line('Response time: '.$this->responseTimeMs.' ms');
        }

        if ($this->monitor->perf_threshold_ms) {
            $mail->line('Threshold: '.$this->monitor->perf_threshold_ms.' ms');
        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'monitor_id' => $this->monitor->id,
            'monitor_name' => $this->monitor->name,
            'url' => $this->monitor->url,
            'response_time_ms' => $this->responseTimeMs,
        ];
    }
}
