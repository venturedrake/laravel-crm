<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorSslNotification extends Notification
{
    use Queueable;

    protected Monitor $monitor;

    protected string $reason;

    protected ?string $detail;

    public function __construct(Monitor $monitor, string $reason, ?string $detail = null)
    {
        $this->monitor = $monitor;
        $this->reason = $reason;
        $this->detail = $detail;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('Monitor SSL alert: '.$this->monitor->name)
            ->line($this->monitor->name.' ('.$this->monitor->url.') has an SSL issue: '.$this->reason);

        if ($this->detail) {
            $mail->line($this->detail);
        }

        if ($this->monitor->ssl_expires_at) {
            $mail->line('Certificate expires at: '.$this->monitor->ssl_expires_at->toDateTimeString().' UTC');
        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'monitor_id' => $this->monitor->id,
            'monitor_name' => $this->monitor->name,
            'url' => $this->monitor->url,
            'reason' => $this->reason,
            'detail' => $this->detail,
        ];
    }
}
