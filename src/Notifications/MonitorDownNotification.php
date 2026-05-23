<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorDownNotification extends Notification
{
    use Queueable;

    protected Monitor $monitor;

    protected ?string $error;

    public function __construct(Monitor $monitor, ?string $error = null)
    {
        $this->monitor = $monitor;
        $this->error = $error;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('Monitor down: '.$this->monitor->name)
            ->line($this->monitor->name.' ('.$this->monitor->url.') is reporting DOWN.');

        if ($this->error) {
            $mail->line('Error: '.$this->error);
        }

        if ($this->monitor->down_since_at) {
            $mail->line('Down since: '.$this->monitor->down_since_at->toDateTimeString().' UTC');
        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'monitor_id' => $this->monitor->id,
            'monitor_name' => $this->monitor->name,
            'url' => $this->monitor->url,
            'error' => $this->error,
        ];
    }
}
