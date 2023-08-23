<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CallReminderNotification extends Notification
{
    use Queueable;

    protected $call;

    protected $user;

    protected $notify;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Call $call, User $user, $notify = 'user')
    {
        $this->call = $call;
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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = new MailMessage();
        $subject = 'CALL REMINDER: '.$this->call->name.' ('.Carbon::parse($this->call->start_at)->format('M d, Y \\@ h:i A').')';
        $greeting = 'Hi '.$this->user->name.',';

        $mailMessage
            ->subject($subject)
            ->greeting($greeting);

        $mailMessage->line(new HtmlString('<strong>THIS CALL IS COMING UP:</strong>'));
        $mailMessage->line(new HtmlString('<strong>'.$this->call->name.'</strong>'));
        $mailMessage->line(new HtmlString('Starting: '.Carbon::parse($this->call->start_at)->format('M d, Y \\@ h:i A').'<br />Ending: '.Carbon::parse($this->call->finish_at)->format('M d, Y \\@ h:i A'). '<br />Location: '.$this->call->location));
        $mailMessage->line(new HtmlString($this->call->description));

        if($this->call->callable) {
            switch(class_basename($this->call->callable->getMorphClass())) {
                case "Lead":
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/leads/'.$this->call->callable->id.'">'.$this->call->callable->title.'</a></small>'));
                    break;

                case "Deal":
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/deals/'.$this->call->callable->id.'">'.$this->call->callable->title.'</a></small>'));
                    break;

                case "Quote":
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/quotes/'.$this->call->callable->id.'">'.$this->call->callable->title.'</a></small>'));
                    break;

                case "Order":
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/orders/'.$this->call->callable->id.'">'.$this->call->callable->order_id.'</a></small>'));
                    break;

                case "Invoice":
                    $mailMessage->line(new HtmlString('Invoice: <a href="'.config('app.url').'/invoices/'.$this->call->callable->id.'">'.$this->call->callable->invoice_id.'</a></small>'));
                    break;

                case "Delivery":
                    $mailMessage->line(new HtmlString('Delivery: <a href="'.config('app.url').'/deliveries/'.$this->call->callable->id.'">'.$this->call->callable->delivery_id.'</a></small>'));
                    break;

                case "Client":
                    $mailMessage->line(new HtmlString('Client: <a href="'.config('app.url').'/clients/'.$this->call->callable->id.'">'.$this->call->callable->name.'</a></small>'));
                    break;

                case "Organisation":
                    $mailMessage->line(new HtmlString('Organisation: <a href="'.config('app.url').'/organisation/'.$this->call->callable->id.'">'.$this->call->callable->name.'</a></small>'));
                    break;

                case "Person":
                    $mailMessage->line(new HtmlString('Person: <a href="'.config('app.url').'/people/'.$this->call->callable->id.'">'.$this->call->callable->name.'</a></small>'));
                    break;
            }
        }

        $placeholders = [
            'call' => $this->call,
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
