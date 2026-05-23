<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorSslExpiringNotification extends Notification
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
            ->subject(ucfirst(__('laravel-crm::lang.monitor_ssl_expiring_subject')).': '.$this->monitor->name)
            ->greeting('Hi '.$this->owner->name.',')
            ->line('The SSL certificate for '.$this->monitor->name.' ('.$this->monitor->url.') is expiring soon.');

        $expiresAt = $this->result['expires_at'] ?? $this->monitor->ssl_expires_at;

        if ($expiresAt instanceof Carbon) {
            $mail->line('Expires at: '.$expiresAt->toDateTimeString().' UTC');
        }

        if (! empty($this->result['issuer'])) {
            $mail->line('Issuer: '.$this->result['issuer']);
        } elseif ($this->monitor->ssl_issuer) {
            $mail->line('Issuer: '.$this->monitor->ssl_issuer);
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
