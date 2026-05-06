<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\EmailTemplate;

class EmailTemplateService
{
    public function create(array $data): EmailTemplate
    {
        return EmailTemplate::create([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'preview_text' => $data['preview_text'] ?? null,
            'body' => $data['body'],
            'is_system' => false,
        ]);
    }

    public function update(array $data, EmailTemplate $template): EmailTemplate
    {
        if ($template->is_system) {
            return $template;
        }

        $template->update([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'preview_text' => $data['preview_text'] ?? null,
            'body' => $data['body'],
        ]);

        return $template;
    }
}
