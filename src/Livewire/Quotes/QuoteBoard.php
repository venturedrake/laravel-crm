<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteBoard extends KanbanBoard
{
    use Toast;

    public $layout = 'board';

    public $model = 'quote';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $user_id = [];

    #[Url]
    public ?array $label_id = [];

    public bool $showFilters = false;

    public ?Pipeline $pipeline = null;

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
        if ($pipeline = Pipeline::where('model', get_class(new Quote))->first()) {
            return $pipeline->pipelineStages()
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        }
    }

    public function onStageSorted($orderedIds)
    {
        foreach ($orderedIds as $orderNumber => $quoteId) {
            Quote::find($quoteId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        Quote::find($recordId)->update([
            'pipeline_stage_id' => $stageId,
        ]);

        foreach ($fromOrderedIds as $orderNumber => $quoteId) {
            Quote::find($quoteId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }

        foreach ($toOrderedIds as $orderNumber => $quoteId) {
            Quote::find($quoteId)->update([
                'pipeline_stage_order' => $orderNumber + 1,
            ]);
        }
    }

    public function records(): Collection
    {
        $quotes = Quote::select(
            config('laravel-crm.db_table_prefix').'quotes.*',
            config('laravel-crm.db_table_prefix').'people.first_name',
            config('laravel-crm.db_table_prefix').'people.last_name',
            config('laravel-crm.db_table_prefix').'organizations.name'
        )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'quotes.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'quotes.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->when($this->search, function (Builder $q) {
                $q->where(function ($q) {
                    $q->orWhere(config('laravel-crm.db_table_prefix').'quotes.title', 'like', "%$this->search%")
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

        return $quotes->map(function (Quote $quote) {
            return [
                'id' => $quote->id,
                'title' => $quote->title,
                'labels' => $quote->labels,
                'stage' => $quote->pipelineStage->id ?? $this->firstStageId(),
                'number' => $quote->quote_id,
                'amount' => $quote->total,
                'currency' => $quote->currency,
            ];
        });
    }

    public function updatedSearch()
    {
        $this->render();
    }

    public function delete($id)
    {
        if ($quote = Quote::find($id)) {
            $quote->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.quote_deleted')));
        }
    }

    public function accept($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'accepted_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Accepted')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_accepted')));
        }
    }

    public function reject($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'rejected_at' => Carbon::now(),
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Rejected')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_rejected')));
        }
    }

    public function unaccept($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'accepted_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Draft')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unaccepted')));
        }
    }

    public function unreject($id): void
    {
        if ($quote = Quote::find($id)) {
            $quote->update([
                'rejected_at' => null,
                'pipeline_stage_id' => $this->pipeline->pipelineStages()->where('name', 'Draft')->first()->id ?? null,
            ]);

            $this->success(ucfirst(trans('laravel-crm::lang.quote_unrejected')));
        }
    }

    public function send($id): void
    {
        if ($quote = Quote::find($id)) {
            $this->dispatch('quote-send', $quote->id);
        }

    }

    public function render()
    {
        $this->pipeline = Pipeline::where('model', get_class(new Quote))->first();

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

        return view('laravel-crm::livewire.quotes.quote-board', [
            'users' => $this->users(),
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'records' => $records,
            'stages' => $stages,
        ]);
    }
}
