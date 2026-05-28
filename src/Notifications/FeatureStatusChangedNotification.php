<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;

class FeatureStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    protected Feature $feature;

    protected ?FeatureStatus $oldStatus;

    protected ?FeatureStatus $newStatus;

    protected string $recipientRole;

    public function __construct(Feature $feature, ?FeatureStatus $oldStatus, ?FeatureStatus $newStatus, string $recipientRole)
    {
        $this->feature = $feature;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->recipientRole = $recipientRole;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $introKey = $this->recipientRole === 'submitter'
            ? 'feature_status_changed_intro_submitter'
            : 'feature_status_changed_intro_voter';

        $oldName = $this->oldStatus->name ?? '—';
        $newName = $this->newStatus->name ?? '—';

        return (new MailMessage)
            ->subject(__('laravel-crm::lang.feature_status_changed_subject', [
                'id' => $this->feature->feature_id,
                'title' => $this->feature->title,
            ]))
            ->greeting(__('laravel-crm::lang.hello').' '.($notifiable->name ?? '').',')
            ->line(__('laravel-crm::lang.'.$introKey, [
                'title' => $this->feature->title,
            ]))
            ->line(__('laravel-crm::lang.feature_status_changed_from_to', [
                'old' => $oldName,
                'new' => $newName,
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
            'old_status' => $this->oldStatus->name ?? null,
            'new_status' => $this->newStatus->name ?? null,
            'recipient_role' => $this->recipientRole,
        ];
    }
}
