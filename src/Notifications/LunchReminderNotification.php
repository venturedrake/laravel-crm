<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\Lunch;

class LunchReminderNotification extends Notification
{
    use Queueable;

    protected $lunch;

    protected $user;

    protected $notify;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Lunch $lunch, User $user, $notify = 'user')
    {
        $this->lunch = $lunch;
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
        $subject = 'LUNCH REMINDER: '.e($this->lunch->name).' ('.Carbon::parse($this->lunch->start_at)->format('M d, Y \\@ h:i A').')';
        $greeting = 'Hi '.e($this->user->name).',';

        $mailMessage
            ->subject($subject)
            ->greeting($greeting);

        $mailMessage->line(new HtmlString('<strong>THIS LUNCH IS COMING UP:</strong>'));
        $mailMessage->line(new HtmlString('<strong>'.e($this->lunch->name).'</strong>'));
        $mailMessage->line(new HtmlString('Starting: '.Carbon::parse($this->lunch->start_at)->format('M d, Y \\@ h:i A').'<br />Ending: '.Carbon::parse($this->lunch->finish_at)->format('M d, Y \\@ h:i A').'<br />Location: '.e($this->lunch->location)));
        $mailMessage->line(new HtmlString(nl2br(e($this->lunch->description))));

        if ($this->lunch->lunchable) {
            switch (class_basename($this->lunch->lunchable->getMorphClass())) {
                case 'Lead':
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/leads/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->title).'</a></small>'));
                    break;

                case 'Deal':
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/deals/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->title).'</a></small>'));
                    break;

                case 'Quote':
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/quotes/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->title).'</a></small>'));
                    break;

                case 'Order':
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/orders/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->order_id).'</a></small>'));
                    break;

                case 'Invoice':
                    $mailMessage->line(new HtmlString('Invoice: <a href="'.config('app.url').'/invoices/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->invoice_id).'</a></small>'));
                    break;

                case 'Delivery':
                    $mailMessage->line(new HtmlString('Delivery: <a href="'.config('app.url').'/deliveries/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->delivery_id).'</a></small>'));
                    break;

                case 'Client':
                    $mailMessage->line(new HtmlString('Client: <a href="'.config('app.url').'/clients/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->name).'</a></small>'));
                    break;

                case 'Organization':
                    $mailMessage->line(new HtmlString('Organization: <a href="'.config('app.url').'/organization/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->name).'</a></small>'));
                    break;

                case 'Person':
                    $mailMessage->line(new HtmlString('Person: <a href="'.config('app.url').'/people/'.e($this->lunch->lunchable->id).'">'.e($this->lunch->lunchable->name).'</a></small>'));
                    break;
            }
        }

        $placeholders = [
            'lunch' => $this->lunch,
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
