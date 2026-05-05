<?php

namespace VentureDrake\LaravelCrm\Repositories;

use VentureDrake\LaravelCrm\Models\ChatConversation;

class ChatRepository
{
    public function all()
    {
        return ChatConversation::all();
    }

    public function find($id)
    {
        return ChatConversation::find($id);
    }
}
