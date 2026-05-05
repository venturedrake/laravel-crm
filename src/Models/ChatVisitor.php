<?php

namespace VentureDrake\LaravelCrm\Models;

use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class ChatVisitor extends Model
{
    use BelongsToTeams;

    protected $guarded = ['id'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'chat_visitors';
    }

    public function widget()
    {
        return $this->belongsTo(ChatWidget::class, 'chat_widget_id');
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function pageViews()
    {
        return $this->hasMany(ChatVisitorPageView::class)->orderByDesc('viewed_at');
    }

    public function displayName(): string
    {
        return $this->name ?: 'Visitor #'.$this->id;
    }

    /**
     * Visitor is "online" if they've pinged the API within the last 60 seconds.
     * The widget polls every 4 s, so a fresh tab will always be online.
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subSeconds(60));
    }
}
