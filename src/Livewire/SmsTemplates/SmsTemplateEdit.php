<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsTemplates;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\SmsTemplateService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

class SmsTemplateEdit extends Component
{
    use Toast;

    public SmsTemplate $template;

    public ?string $name = null;

    public ?string $body = null;

    public bool $showPreview = false;

    public string $previewText = '';

    public function mount(SmsTemplate $template): void
    {
        if ($template->is_system) {
            $this->error(ucfirst(__('laravel-crm::lang.sms_template')).' '.__('laravel-crm::lang.is_system_readonly'), redirectTo: route('laravel-crm.sms-templates.show', $template));

            return;
        }

        $this->template = $template;
        $this->name = $template->name;
        $this->body = $template->body;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'body' => 'required|string|max:1530',
        ];
    }

    public function save(SmsTemplateService $service)
    {
        $this->validate();

        $service->update([
            'name' => $this->name,
            'body' => $this->body,
        ], $this->template);

        $this->success(
            ucfirst(__('laravel-crm::lang.sms_template')).' '.__('laravel-crm::lang.updated'),
            redirectTo: route('laravel-crm.sms-templates.show', $this->template)
        );
    }

    public function openPreview(): void
    {
        $this->previewText = SmsCampaignMessage::renderPreview($this->body ?? '');
        $this->showPreview = true;
    }

    public function getSegmentCountProperty(): int
    {
        return SmsCampaignMessage::segmentCount($this->body ?? '');
    }

    public function render()
    {
        return view('laravel-crm::livewire.sms-templates.sms-template-edit', [
            'placeholders' => SmsCampaignMessage::availablePlaceholders(),
        ]);
    }
}
