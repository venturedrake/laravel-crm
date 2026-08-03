<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\Meeting;

class MeetingReminderNotification extends Notification
{
    use Queueable;

    protected $meeting;

    protected $user;

    protected $notify;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Meeting $meeting, User $user, $notify = 'user')
    {
        $this->meeting = $meeting;
        $this->user = $user;
        $this->notify = $notify;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = new MailMessage;
        $subject = 'MEETING REMINDER: '.e($this->meeting->name).' ('.Carbon::parse($this->meeting->start_at)->format('M d, Y \\@ h:i A').')';
        $greeting = 'Hi '.e($this->user->name).',';

        $mailMessage
            ->subject($subject)
            ->greeting($greeting);

        $mailMessage->line(new HtmlString('<strong>THIS MEETING IS COMING UP:</strong>'));
        $mailMessage->line(new HtmlString('<strong>'.e($this->meeting->name).'</strong>'));
        $mailMessage->line(new HtmlString('Starting: '.Carbon::parse($this->meeting->start_at)->format('M d, Y \\@ h:i A').'<br />Ending: '.Carbon::parse($this->meeting->finish_at)->format('M d, Y \\@ h:i A').'<br />Location: '.e($this->meeting->location)));
        $mailMessage->line(new HtmlString(nl2br(e($this->meeting->description))));

        if ($this->meeting->meetingable) {
            switch (class_basename($this->meeting->meetingable->getMorphClass())) {
                case 'Lead':
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/leads/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->title).'</a></small>'));
                    break;

                case 'Deal':
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/deals/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->title).'</a></small>'));
                    break;

                case 'Quote':
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/quotes/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->title).'</a></small>'));
                    break;

                case 'Order':
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/orders/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->order_id).'</a></small>'));
                    break;

                case 'Invoice':
                    $mailMessage->line(new HtmlString('Invoice: <a href="'.config('app.url').'/invoices/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->invoice_id).'</a></small>'));
                    break;

                case 'Delivery':
                    $mailMessage->line(new HtmlString('Delivery: <a href="'.config('app.url').'/deliveries/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->delivery_id).'</a></small>'));
                    break;

                case 'Client':
                    $mailMessage->line(new HtmlString('Client: <a href="'.config('app.url').'/clients/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->name).'</a></small>'));
                    break;

                case 'Organization':
                    $mailMessage->line(new HtmlString('Organization: <a href="'.config('app.url').'/organization/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->name).'</a></small>'));
                    break;

                case 'Person':
                    $mailMessage->line(new HtmlString('Person: <a href="'.config('app.url').'/people/'.e($this->meeting->meetingable->id).'">'.e($this->meeting->meetingable->name).'</a></small>'));
                    break;
            }
        }

        $placeholders = [
            'meeting' => $this->meeting,
            'user' => $this->user,
        ];

        $mailMessage->markdown('laravel-crm::notifications.email', $placeholders);

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
