<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class ChatConversation extends Model
{
    use BelongsToTeams;
    use HasGlobalSettings;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $searchable = ['subject', 'chat_id'];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'chat_conversations';
    }

    public function widget()
    {
        return $this->belongsTo(ChatWidget::class, 'chat_widget_id');
    }

    public function visitor()
    {
        return $this->belongsTo(ChatVisitor::class, 'chat_visitor_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
    }

    public function unreadForAgents(): int
    {
        return $this->messages()->where('sender_type', 'visitor')->whereNull('read_at')->count();
    }

    /**
     * Public broadcast channel name (visitor side may not be authenticated).
     */
    public function channelName(): string
    {
        return 'crm-chat.'.$this->external_id;
    }
}
