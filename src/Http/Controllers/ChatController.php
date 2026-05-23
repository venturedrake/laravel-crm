<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\ChatConversation;

class ChatController extends Controller
{
    public function index()
    {
        return view('laravel-crm::chat.index');
    }

    public function show(ChatConversation $chat)
    {
        return view('laravel-crm::chat.show', ['conversation' => $chat]);
    }

    public function destroy(ChatConversation $chat)
    {
        $chat->delete();

        flash()->success(ucfirst(trans('laravel-crm::lang.chat_deleted')));

        return redirect(route('laravel-crm.chat.index'));
    }
}
