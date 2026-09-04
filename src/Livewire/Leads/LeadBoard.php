<?php

namespace VentureDrake\LaravelCrm\Livewire\Leads;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;

class LeadBoard extends KanbanBoard
{
    use AuthorizesRequests, Toast;

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
        $records = $this->resolve($orderedIds);

        foreach ($records as $record) {
            $this->authorize('update', $record);
        }

        DB::transaction(function () use ($records) {
            $this->reorder($records);
        });
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        if (! $record = Lead::find($recordId)) {
            return;
        }

        $from = $this->resolve($fromOrderedIds);
        $to = $this->resolve($toOrderedIds);

        foreach ($from->merge($to)->concat([$record]) as $authorizable) {
            $this->authorize('update', $authorizable);
        }

        DB::transaction(function () use ($record, $stageId, $from, $to) {
            $record->update([
                'pipeline_stage_id' => $stageId,
            ]);

            $this->reorder($from);
            $this->reorder($to);
        });
    }

    /**
     * Resolve the ids the board sent us, dropping anything that is not a lead.
     *
     * A stage container holds more than the record cards -- each card is followed by its
     * delete-confirm dialog -- so a host still serving a stale published copy of
     * kanban-board/sortable.blade.php harvests ids like 'modalDeleteLead4' alongside the
     * real ones. Those have to be skipped rather than dereferenced, and they must not
     * consume an order number either.
     */
    private function resolve(array $orderedIds): Collection
    {
        return collect($orderedIds)
            ->map(fn ($leadId) => Lead::find($leadId))
            ->filter()
            ->values();
    }

    /**
     * Renumber a stage contiguously from 1. Callers authorize the whole batch first, so a
     * denial cannot leave the stage half renumbered.
     */
    private function reorder(Collection $records): void
    {
        $records->each(fn (Lead $record, int $index) => $record->update([
            'pipeline_stage_order' => $index + 1,
        ]));
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
            ->when($this->label_id, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereIn(config('laravel-crm.db_table_prefix').'labels.id', $this->label_id)))
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

    public function delete($id)
    {
        if ($lead = Lead::find($id)) {
            $this->authorize('delete', $lead);

            $lead->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.lead_deleted')));
        }
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

        $this->dispatch('board-loaded');

        return view('laravel-crm::livewire.leads.lead-board', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
