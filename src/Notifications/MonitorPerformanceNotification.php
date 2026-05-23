<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorPerformanceNotification extends Notification
{
    use Queueable;

    protected Monitor $monitor;

    protected User $owner;

    protected array $result;

    public function __construct(Monitor $monitor, User $owner, array $result = [])
    {
        $this->monitor = $monitor;
        $this->owner = $owner;
        $this->result = $result;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject(ucfirst(__('laravel-crm::lang.monitor_perf_subject')).': '.$this->monitor->name)
            ->greeting('Hi '.$this->owner->name.',')
            ->line($this->monitor->name.' ('.$this->monitor->url.') is responding slowly.');

        if (isset($this->result['response_time_ms']) && $this->result['response_time_ms'] !== null) {
            $mail->line('Response time: '.$this->result['response_time_ms'].' ms');
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
            'result' => $this->result,
        ];
    }
}
