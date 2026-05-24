<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorRecoveredNotification extends Notification
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
            ->subject(ucfirst(__('laravel-crm::lang.monitor_recovered_subject')).': '.$this->monitor->displayName())
            ->greeting('Hi '.$this->owner->name.',')
            ->line($this->monitor->displayName().' ('.$this->monitor->url.') is back up.')
            ->line('Status: '.($this->monitor->last_status ?? 'up'));

        if (! empty($this->result['response_time_ms'])) {
            $mail->line('Response time: '.$this->result['response_time_ms'].' ms');
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
