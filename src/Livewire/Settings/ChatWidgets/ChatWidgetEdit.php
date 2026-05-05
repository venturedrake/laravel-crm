<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\ChatWidgets;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\ChatWidget;

class ChatWidgetEdit extends Component
{
    use Toast;

    public ?ChatWidget $widget = null;

    public string $name = '';

    public string $welcome_message = '';

    public string $color = '#2563eb';

    public string $position = 'bottom-right';

    public bool $is_active = true;

    public function mount(?ChatWidget $widget = null): void
    {
        if ($widget && $widget->exists) {
            $this->widget = $widget;
            $this->name = $widget->name;
            $this->welcome_message = (string) $widget->welcome_message;
            $this->color = (string) $widget->color;
            $this->position = (string) $widget->position;
            $this->is_active = (bool) $widget->is_active;
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'welcome_message' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:16',
            'position' => 'in:bottom-right,bottom-left',
            'is_active' => 'boolean',
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->widget) {
            $this->widget->update($data);
            $this->success(ucfirst(trans('laravel-crm::lang.chat_widget_updated')));
        } else {
            $this->widget = ChatWidget::create($data);
            $this->success(ucfirst(trans('laravel-crm::lang.chat_widget_created')));
        }

        return redirect(route('laravel-crm.chat-widgets.show', $this->widget));
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.chat-widgets.chat-widget-edit');
    }
}
