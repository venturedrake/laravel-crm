<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\SmsTemplate;

class SmsTemplateService
{
    public function create(array $data): SmsTemplate
    {
        return SmsTemplate::create([
            'name' => $data['name'],
            'body' => $data['body'],
            'is_system' => false,
        ]);
    }

    public function update(array $data, SmsTemplate $template): SmsTemplate
    {
        if ($template->is_system) {
            return $template;
        }

        $template->update([
            'name' => $data['name'],
            'body' => $data['body'],
        ]);

        return $template;
    }
}
