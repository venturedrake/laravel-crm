<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ChatWidgets;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\ChatWidget;

class ChatWidgetShow extends Component
{
    public ChatWidget $widget;

    public function mount(ChatWidget $widget): void
    {
        $this->widget = $widget;
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.chat-widgets.chat-widget-show', [
            'snippet' => $this->widget->embedSnippet(),
        ]);
    }
}

