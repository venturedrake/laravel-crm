<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

class ChatMessage extends Model
{
    use BelongsToTeams;

    protected $guarded = ['id'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'chat_messages';
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function senderUser()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function senderVisitor()
    {
        return $this->belongsTo(ChatVisitor::class, 'sender_id');
    }

    public function senderName(): string
    {
        if ($this->sender_type === 'user') {
            return $this->senderUser?->name ?? 'Agent';
        }
        if ($this->sender_type === 'visitor') {
            return $this->senderVisitor?->displayName() ?? 'Visitor';
        }

        return 'System';
    }

    public function isFromVisitor(): bool
    {
        return $this->sender_type === 'visitor';
    }

    public function isFromAgent(): bool
    {
        return $this->sender_type === 'user';
    }
}

