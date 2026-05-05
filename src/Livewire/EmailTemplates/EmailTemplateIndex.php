<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailTemplates;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\EmailTemplate;

class EmailTemplateIndex extends Component
{
    use Toast;
    use WithPagination;

    public $layout = 'index';

    #[Url]
    public string $search = '';

    #[Url]
    public array $sortBy = ['column' => 'is_system', 'direction' => 'desc'];

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'subject', 'label' => ucfirst(__('laravel-crm::lang.subject'))],
            ['key' => 'is_system', 'label' => ucfirst(__('laravel-crm::lang.type'))],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created'))],
        ];
    }

    public function templates(): LengthAwarePaginator
    {
        return EmailTemplate::query()
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', "%$this->search%")
                        ->orWhere('subject', 'like', "%$this->search%");
                });
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($template = EmailTemplate::find($id)) {
            if ($template->is_system) {
                $this->error(ucfirst(__('laravel-crm::lang.email_template')).' '.__('laravel-crm::lang.is_system_readonly'));

                return;
            }

            $template->delete();
            $this->success(ucfirst(__('laravel-crm::lang.email_template')).' '.__('laravel-crm::lang.deleted'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-templates.email-template-index', [
            'headers' => $this->headers(),
            'templates' => $this->templates(),
        ]);
    }
}
