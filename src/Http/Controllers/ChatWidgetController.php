<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\ChatWidget;

class ChatWidgetController extends Controller
{
    public function index()
    {
        return view('laravel-crm::chat-widgets.index');
    }

    public function create()
    {
        return view('laravel-crm::chat-widgets.create');
    }

    public function show(ChatWidget $chatWidget)
    {
        return view('laravel-crm::chat-widgets.show', ['widget' => $chatWidget]);
    }

    public function edit(ChatWidget $chatWidget)
    {
        return view('laravel-crm::chat-widgets.edit', ['widget' => $chatWidget]);
    }

    public function destroy(ChatWidget $chatWidget)
    {
        $chatWidget->delete();
        flash(ucfirst(trans('laravel-crm::lang.chat_widget_deleted')))->success()->important();

        return redirect(route('laravel-crm.chat-widgets.index'));
    }
}

