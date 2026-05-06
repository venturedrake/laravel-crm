<?php

namespace VentureDrake\LaravelCrm\Livewire\SmsTemplates;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\SmsTemplateService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;

class SmsTemplateCreate extends Component
{
    use AuthorizesRequests;
    use Toast;

    public ?string $name = null;

    public ?string $body = null;

    public ?int $clone_from = null;

    public bool $showPreview = false;

    public string $previewText = '';

    public function mount(): void
    {
        if (request()->has('clone_from')) {
            $source = SmsTemplate::find(request()->clone_from);

            if ($source) {
                $this->name = $source->name.' (copy)';
                $this->body = $source->body;
                $this->clone_from = $source->id;
            }
        }
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
        $this->authorize('create', SmsTemplate::class);

        $this->validate();

        $template = $service->create([
            'name' => $this->name,
            'body' => $this->body,
        ]);

        $this->success(
            ucfirst(__('laravel-crm::lang.sms_template')).' '.__('laravel-crm::lang.created'),
            redirectTo: route('laravel-crm.sms-templates.show', $template)
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
        return view('laravel-crm::livewire.sms-templates.sms-template-create', [
            'placeholders' => SmsCampaignMessage::availablePlaceholders(),
        ]);
    }
}
