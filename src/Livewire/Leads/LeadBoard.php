<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;

class LeadBoard extends KanbanBoard
{
    public $layout = 'board';

    public $model = 'lead';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    public bool $showFilters = false;

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0);
    }

    public function users(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function labels(): Collection
    {
        return Label::all();
    }

    public function stages(): Collection
    {
        if ($pipeline = Pipeline::where('model', get_class(new Lead))->first()) {
            return $pipeline->pipelineStages()
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        }
    }

    public function onStageSorted($orderedIds)
    {
        foreach ($orderedIds as $orderNumber => $leadId) {
            Lead::find($leadId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        Lead::find($recordId)->update([
            'pipeline_stage_id' => $stageId,
        ]);

        foreach ($fromOrderedIds as $orderNumber => $leadId) {
            Lead::find($leadId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }

        foreach ($toOrderedIds as $orderNumber => $leadId) {
            Lead::find($leadId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }
    }

    public function records(): Collection
    {
        $leads = Lead::whereNull('converted_at')
            ->select(
                config('laravel-crm.db_table_prefix').'leads.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'organizations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'leads.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'leads.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->orWhere(config('laravel-crm.db_table_prefix').'leads.title', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'organizations.name', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'people.first_name', 'like', "%$this->search%")
                        ->orWhere(config('laravel-crm.db_table_prefix').'people.last_name', 'like', "%$this->search%")
                        ->orWhereRaw('CONCAT('.config('laravel-crm.db_table_prefix')."people.first_name, ' ', ".config('laravel-crm.db_table_prefix').'people.last_name) like ?', ["%$this->search%"]);
                });
            })
            ->when($this->user_id, fn (Builder $q) => $q->whereIn('user_owner_id', $this->user_id))
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn('labels.id', $this->label_id)))
            ->orderBy('pipeline_stage_order')
            ->oldest()
            ->get();

        return $leads->map(function (Lead $lead) {
            return [
                'id' => $lead->id,
                'title' => $lead->title,
                'labels' => $lead->labels,
                'stage' => $lead->pipelineStage->id ?? $this->firstStageId(),
                'number' => $lead->lead_id,
                'amount' => $lead->amount,
                'currency' => $lead->currency,
            ];
        });
    }

    public function updatedSearch()
    {
        $this->render();
    }

    public function render()
    {
        $stages = $this->stages();

        $records = $this->records();

        $stages = $stages
            ->map(function ($stage) use ($records) {
                $stage['group'] = $this->id();
                $stage['stageRecordsId'] = "{$this->id()}-{$stage['id']}";
                $stage['records'] = $records
                    ->filter(function ($record) use ($stage) {
                        return $this->isRecordInStage($record, $stage);
                    });

                return $stage;
            });

        return view('laravel-crm::livewire.leads.lead-board', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
