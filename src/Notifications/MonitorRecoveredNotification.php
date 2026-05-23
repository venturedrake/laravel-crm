<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorRecoveredNotification extends Notification
{
    use Queueable;

    protected Monitor $monitor;

    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Monitor recovered: '.$this->monitor->name)
            ->line($this->monitor->name.' ('.$this->monitor->url.') is back up.')
            ->line('Status: '.($this->monitor->last_status ?? 'up'));
    }

    public function toArray($notifiable)
    {
        return [
            'monitor_id' => $this->monitor->id,
            'monitor_name' => $this->monitor->name,
            'url' => $this->monitor->url,
        ];
    }
}
