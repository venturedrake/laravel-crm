<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorDownNotification extends Notification
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
            ->subject(ucfirst(__('laravel-crm::lang.monitor_down_subject')).': '.$this->monitor->name)
            ->greeting('Hi '.$this->owner->name.',')
            ->line($this->monitor->name.' ('.$this->monitor->url.') is reporting DOWN.');

        if (! empty($this->result['error'])) {
            $mail->line('Error: '.$this->result['error']);
        }

        if (! empty($this->result['status_code'])) {
            $mail->line('Status code: '.$this->result['status_code']);
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
            'result' => $this->result,
        ];
    }
}
