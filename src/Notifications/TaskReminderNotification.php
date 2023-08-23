<?php

namespace VentureDrake\LaravelCrm\Notifications;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification
{
    use Queueable;

    protected $task;

    protected $user;

    protected $notify;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Task $task, User $user, $notify = 'user')
    {
        $this->task = $task;
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
        $subject = 'TASK REMINDER: '.$this->task->name.' ('.Carbon::parse($this->task->due_at)->format('M d, Y \\@ h:i A').')';
        $greeting = 'Hi '.$this->user->name.',';

        $mailMessage
            ->subject($subject)
            ->greeting($greeting);

        $mailMessage->line(new HtmlString('<strong>THIS TASK IS DUE:</strong>'));
        $mailMessage->line(new HtmlString('<strong>'.$this->task->name.'</strong>'));
        $mailMessage->line(new HtmlString($this->task->description));

        if($this->task->taskable) {
            switch(class_basename($this->task->taskable->getMorphClass())) {
                case "Lead":
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/leads/'.$this->task->taskable->id.'">'.$this->task->taskable->title.'</a></small>'));
                    break;

                case "Deal":
                    $mailMessage->line(new HtmlString('Lead: <a href="'.config('app.url').'/deals/'.$this->task->taskable->id.'">'.$this->task->taskable->title.'</a></small>'));
                    break;

                case "Quote":
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/quotes/'.$this->task->taskable->id.'">'.$this->task->taskable->title.'</a></small>'));
                    break;

                case "Order":
                    $mailMessage->line(new HtmlString('Quote: <a href="'.config('app.url').'/orders/'.$this->task->taskable->id.'">'.$this->task->taskable->order_id.'</a></small>'));
                    break;

                case "Invoice":
                    $mailMessage->line(new HtmlString('Invoice: <a href="'.config('app.url').'/invoices/'.$this->task->taskable->id.'">'.$this->task->taskable->invoice_id.'</a></small>'));
                    break;

                case "Delivery":
                    $mailMessage->line(new HtmlString('Delivery: <a href="'.config('app.url').'/deliveries/'.$this->task->taskable->id.'">'.$this->task->taskable->delivery_id.'</a></small>'));
                    break;

                case "Client":
                    $mailMessage->line(new HtmlString('Client: <a href="'.config('app.url').'/clients/'.$this->task->taskable->id.'">'.$this->task->taskable->name.'</a></small>'));
                    break;

                case "Organisation":
                    $mailMessage->line(new HtmlString('Organisation: <a href="'.config('app.url').'/organisation/'.$this->task->taskable->id.'">'.$this->task->taskable->name.'</a></small>'));
                    break;

                case "Person":
                    $mailMessage->line(new HtmlString('Person: <a href="'.config('app.url').'/people/'.$this->task->taskable->id.'">'.$this->task->taskable->name.'</a></small>'));
                    break;
            }
        }

        $placeholders = [
            'task' => $this->task,
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
