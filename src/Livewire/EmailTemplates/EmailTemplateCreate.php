<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailTemplates;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Services\EmailTemplateService;

class EmailTemplateCreate extends Component
{
    use Toast;

    public ?string $name = null;

    public ?string $subject = null;

    public ?string $body = null;

    public ?int $clone_from = null;

    public function mount(): void
    {
        if (request()->has('clone_from')) {
            $source = EmailTemplate::find(request()->clone_from);

            if ($source) {
                $this->name = $source->name.' (copy)';
                $this->subject = $source->subject;
                $this->body = $source->body;
                $this->clone_from = $source->id;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ];
    }

    public function save(EmailTemplateService $service)
    {
        $this->validate();

        $template = $service->create([
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
        ]);

        $this->success(
            ucfirst(__('laravel-crm::lang.email_template')).' '.__('laravel-crm::lang.created'),
            redirectTo: route('laravel-crm.email-templates.show', $template)
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-templates.email-template-create');
    }
}
