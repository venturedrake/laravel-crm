<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Livewire\PipelineBoard;
use VentureDrake\LaravelCrm\Models\Lead;

class LeadBoard extends PipelineBoard
{
    public $model = 'lead';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    public bool $showFilters = false;

    protected function boardModel(): string
    {
        return Lead::class;
    }

    protected function boardScope(Builder $query): void
    {
        $query->whereNull($this->column('converted_at'));
    }

    protected function cardColumns(): array
    {
        return ['id', 'lead_id', 'title', 'amount', 'currency'];
    }

    public function delete($id)
    {
        if ($lead = Lead::find($id)) {
            $this->authorize('delete', $lead);

            $lead->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.lead_deleted')));
        }
    }
}
