<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorSslInvalidNotification extends Notification
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
            ->error()
            ->subject(ucfirst(__('laravel-crm::lang.monitor_ssl_invalid_subject')).': '.$this->monitor->displayName())
            ->greeting('Hi '.$this->owner->name.',')
            ->line('The SSL certificate for '.$this->monitor->displayName().' ('.$this->monitor->url.') is invalid.');

        if (! empty($this->result['error'])) {
            $mail->line('Reason: '.$this->result['error']);
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
