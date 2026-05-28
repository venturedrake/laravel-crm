<?php

namespace VentureDrake\LaravelCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\FeatureComment;

class FeatureCommentPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    protected FeatureComment $comment;

    protected string $recipientRole;

    public function __construct(FeatureComment $comment, string $recipientRole)
    {
        $comment->loadMissing(['feature', 'createdByUser']);

        $this->comment = $comment;
        $this->recipientRole = $recipientRole;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $introKey = $this->recipientRole === 'owner'
            ? 'feature_comment_posted_intro_owner'
            : 'feature_comment_posted_intro_admin';

        $feature = $this->comment->feature;
        $author = $this->comment->createdByUser->name ?? 'Anonymous';
        $excerpt = Str::limit((string) $this->comment->body, 200);

        return (new MailMessage)
            ->subject(__('laravel-crm::lang.feature_comment_posted_subject', [
                'id' => $feature->feature_id ?? null,
                'title' => $feature->title ?? null,
            ]))
            ->greeting(__('laravel-crm::lang.hello').' '.($notifiable->name ?? '').',')
            ->line(__('laravel-crm::lang.'.$introKey, [
                'title' => $feature->title ?? null,
                'author' => $author,
                'excerpt' => $excerpt,
            ]))
            ->action(
                ucfirst(__('laravel-crm::lang.view_feature')),
                route('laravel-crm.features.show', $feature->external_id ?? '')
            );
    }

    public function toArray($notifiable)
    {
        $feature = $this->comment->feature;

        return [
            'feature_id' => $feature->id ?? null,
            'feature_human_id' => $feature->feature_id ?? null,
            'title' => $feature->title ?? null,
            'comment_id' => $this->comment->id,
            'recipient_role' => $this->recipientRole,
        ];
    }
}
