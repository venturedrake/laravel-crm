<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    protected Feature $feature;

    public function __construct(Feature $feature)
    {
        $this->feature = $feature;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $submitter = $this->feature->submittedBy->name ?? 'Anonymous';
        $excerpt = Str::limit((string) $this->feature->description, 200);

        return (new MailMessage)
            ->subject(__('laravel-crm::lang.feature_submitted_subject', [
                'id' => $this->feature->feature_id,
            ]))
            ->greeting(__('laravel-crm::lang.hello').' '.($notifiable->name ?? '').',')
            ->line(__('laravel-crm::lang.feature_submitted_intro', [
                'title' => $this->feature->title,
                'submitter' => $submitter,
                'excerpt' => $excerpt,
            ]))
            ->action(
                ucfirst(__('laravel-crm::lang.view_feature')),
                route('laravel-crm.features.show', $this->feature->external_id)
            );
    }

    public function toArray($notifiable)
    {
        return [
            'feature_id' => $this->feature->id,
            'feature_human_id' => $this->feature->feature_id,
            'title' => $this->feature->title,
        ];
    }
}
