<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasUserOwnerFilter;
use VentureDrake\LaravelCrm\Livewire\Traits\SearchesEncryptableContacts;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;

/**
 * A board whose columns are the stages of a pipeline, paged per column.
 *
 * Rendering every record cost tens of megabytes of HTML, so each column renders
 * its first `laravel-crm.board.per_column` cards and fetches the rest on demand.
 * The query count of a render depends on the number of stages, not of records.
 *
 * Expects the using component to declare `$search`, `$user_id` and `$label_id`.
 */
abstract class PipelineBoard extends KanbanBoard
{
    use AuthorizesRequests, HasUserOwnerFilter, SearchesEncryptableContacts, Toast;

    public $layout = 'board';

    /**
     * Cards rendered so far per stage id, which is also the offset of the next page.
     * A stage without an entry shows the default page size.
     */
    #[Locked]
    public array $stageLimits = [];

    protected ?Pipeline $boardPipeline = null;

    protected ?Collection $boardStages = null;

    protected ?array $contactMatches = null;

    /**
     * The model class the board shows.
     */
    abstract protected function boardModel(): string;

    /**
     * The columns a card reads, so a page does not hydrate whole records.
     */
    abstract protected function cardColumns(): array;

    /**
     * The column the header totals sum.
     */
    protected function amountColumn(): string
    {
        return 'amount';
    }

    /**
     * Narrow the records that belong on the board at all.
     */
    protected function boardScope(Builder $query): void
    {
        //
    }

    /**
     * The relations a card reads.
     */
    protected function eagerLoads(): array
    {
        return ['labels'];
    }

    /**
     * The query a page of cards is hydrated from.
     */
    protected function cardQuery(): Builder
    {
        return $this->boardModel()::query()
            ->select($this->cardColumns())
            ->with($this->eagerLoads());
    }

    /**
     * Load whatever only some of a page's cards need.
     */
    protected function prepareCards(Collection $records): void
    {
        //
    }

    protected function toCard(Model $record): array
    {
        return [
            'id' => $record->id,
            'title' => $record->title,
            'labels' => $record->labels,
            'number' => $record->{$this->model.'_id'},
            'amount' => $record->{$this->amountColumn()},
            'currency' => $record->currency,
            'model' => $record,
        ];
    }

    public function filterCount(): int
    {
        return (count($this->user_id) > 0 ? 1 : 0) + ($this->label_id ? 1 : 0);
    }

    public function labels(): Collection
    {
        return Label::select('id', 'name')->get();
    }

    protected function pipeline(): ?Pipeline
    {
        return $this->boardPipeline ??= Pipeline::where('model', $this->boardModel())->first();
    }

    public function stages(): Collection
    {
        return $this->boardStages ??= $this->pipeline()?->pipelineStages()
            ->orderBy('order')
            ->orderBy('id')
            ->get() ?? collect();
    }

    protected function stageIdNamed(string $name): ?int
    {
        return $this->pipeline()?->pipelineStages()->where('name', $name)->first()?->id;
    }

    protected function table(): string
    {
        return (new ($this->boardModel()))->getTable();
    }

    protected function column(string $column): string
    {
        return $this->table().'.'.$column;
    }

    protected function perColumn(): int
    {
        return max(1, (int) config('laravel-crm.board.per_column', 20));
    }

    protected function stageLimit(int $stageId): int
    {
        return $this->stageLimits[$stageId] ?? $this->perColumn();
    }

    protected function boardQuery(): Builder
    {
        $query = $this->boardModel()::query();

        $this->boardScope($query);

        return $query;
    }

    /**
     * The records the board shows under the current search and filters.
     */
    protected function filteredQuery(): Builder
    {
        return $this->boardQuery()
            ->when($this->search, fn (Builder $query) => $this->applySearch($query))
            ->when($this->user_id, fn (Builder $query) => $query->whereIn($this->column('user_owner_id'), $this->user_id))
            ->when($this->label_id, fn (Builder $query) => $query->whereHas('labels', fn (Builder $query) => $query->whereIn(config('laravel-crm.db_table_prefix').'labels.id', $this->label_id)));
    }

    /**
     * With encryption on, names are ciphertext in the database, so a LIKE on them
     * can only ever match nothing. The ids are matched in PHP instead, once per
     * request because every column query comes through here.
     */
    protected function applySearch(Builder $query): void
    {
        $term = $this->search;

        if ($this->encryptionEnabled()) {
            $this->contactMatches ??= [
                'person_id' => $this->matchingPersonIds($term),
                'organization_id' => $this->matchingOrganizationIds($term),
            ];

            $query->where(function (Builder $query) use ($term) {
                $query->where($this->column('title'), 'like', "%$term%");

                foreach ($this->contactMatches as $column => $ids) {
                    if ($ids->isNotEmpty()) {
                        $query->orWhereIn($this->column($column), $ids);
                    }
                }
            });

            return;
        }

        $prefix = config('laravel-crm.db_table_prefix');

        $query
            ->leftJoin($prefix.'people', $this->column('person_id'), '=', $prefix.'people.id')
            ->leftJoin($prefix.'organizations', $this->column('organization_id'), '=', $prefix.'organizations.id')
            ->where(function (Builder $query) use ($prefix, $term) {
                $query->where($this->column('title'), 'like', "%$term%")
                    ->orWhere($prefix.'organizations.name', 'like', "%$term%")
                    ->orWhere($prefix.'people.first_name', 'like', "%$term%")
                    ->orWhere($prefix.'people.last_name', 'like', "%$term%")
                    ->orWhereRaw('CONCAT('.$prefix."people.first_name, ' ', ".$prefix.'people.last_name) like ?', ["%$term%"]);
            });
    }

    /**
     * A record whose stage is missing or deleted shows in the first column.
     */
    protected function constrainToStage(Builder $query, int $stageId): Builder
    {
        return $query->where(function (Builder $query) use ($stageId) {
            $query->where($this->column('pipeline_stage_id'), $stageId);

            if ($stageId === $this->firstStageId()) {
                $query->orDoesntHave('pipelineStage');
            }
        });
    }

    /**
     * One page of a column, in display order. The id settles ties on created_at,
     * without which consecutive pages can repeat or skip a card.
     *
     * @return array<int, int>
     */
    protected function pageIds(int $stageId, int $offset, int $limit): array
    {
        return $this->constrainToStage($this->filteredQuery(), $stageId)
            ->orderBy($this->column('pipeline_stage_order'))
            ->orderBy($this->column('created_at'))
            ->orderBy($this->column('id'))
            ->offset($offset)
            ->limit($limit)
            ->pluck($this->column('id'))
            ->all();
    }

    /**
     * The cards for these ids, keyed by id.
     */
    protected function cards(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $records = $this->cardQuery()->whereIn('id', $ids)->get();

        $this->prepareCards($records);

        return $records->keyBy('id')->map(fn (Model $record) => $this->toCard($record))->toBase();
    }

    /**
     * Count, per-currency totals and whether more cards remain, for every stage.
     *
     * @return array<int, array{count: int, totals: array<int, string>, hasMore: bool}>
     */
    protected function columnSummaries(): array
    {
        $summaries = $this->stages()
            ->mapWithKeys(fn ($stage) => [$stage->id => ['count' => 0, 'totals' => []]])
            ->all();

        $rows = $this->filteredQuery()
            ->toBase()
            ->selectRaw(sprintf(
                '%1$s as stage_id, %2$s as currency, COUNT(*) as records, SUM(%3$s) as total',
                $this->column('pipeline_stage_id'),
                $this->column('currency'),
                $this->column($this->amountColumn()),
            ))
            ->groupBy($this->column('pipeline_stage_id'), $this->column('currency'))
            ->get();

        // A stage id with no column is either deleted, which belongs in the first
        // column with the records that have none, or from another pipeline, which
        // stays off the board.
        $unknown = $rows->pluck('stage_id')->filter()->unique()->diff(array_keys($summaries));
        $otherPipelines = $unknown->isEmpty() ? collect() : PipelineStage::whereIn('id', $unknown)->pluck('id');

        foreach ($rows as $row) {
            $stageId = isset($summaries[$row->stage_id]) ? (int) $row->stage_id : $this->firstStageId();

            if ($stageId === null || $otherPipelines->contains($row->stage_id)) {
                continue;
            }

            $summaries[$stageId]['count'] += (int) $row->records;
            $currency = $row->currency ?? '';
            $summaries[$stageId]['totals'][$currency] = ($summaries[$stageId]['totals'][$currency] ?? 0) + (int) $row->total;
        }

        foreach ($summaries as $stageId => $summary) {
            $summaries[$stageId] = [
                'count' => $summary['count'],
                'totals' => collect($summary['totals'])
                    ->filter()
                    ->map(fn (int $total, string $currency) => (string) money($total, $currency ?: null))
                    ->values()
                    ->all(),
                'hasMore' => $summary['count'] > $this->stageLimit($stageId),
            ];
        }

        return $summaries;
    }

    public function updated($property): void
    {
        if (in_array(Str::before($property, '.'), ['search', 'user_id', 'label_id'], true)) {
            $this->stageLimits = [];
        }
    }

    /**
     * Fetch the next page of a column. The cards go straight to the client, which
     * appends them: a full render would resend every column for one page.
     */
    public function loadMore($stageId): void
    {
        $this->authorize('viewAny', $this->boardModel());

        abort_unless($stage = $this->stages()->firstWhere('id', $stageId), 404);

        $this->skipRender();

        $offset = $this->stageLimit($stage->id);
        $size = $this->perColumn();

        // One past the page, which answers "is there more?" without a count.
        $ids = $this->pageIds($stage->id, $offset, $size + 1);
        $page = array_slice($ids, 0, $size);

        $this->stageLimits[$stage->id] = $offset + count($page);

        $cards = $this->cards($page);

        $this->dispatch(
            'board-column-loaded',
            stage: $stage->id,
            html: view('laravel-crm::livewire.kanban-board.records', [
                'records' => collect($page)->map(fn (int $id) => $cards->get($id))->filter(),
                'model' => $this->model,
            ])->render(),
            hasMore: count($ids) > $size,
        );
    }

    public function onStageSorted($orderedIds)
    {
        $this->skipRender();

        $records = $this->resolve($orderedIds);

        foreach ($records as $record) {
            $this->authorize('update', $record);
        }

        if ($records->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($records) {
            $this->reorder($records, $this->columnOf($records->first()), $records);
        });
    }

    public function onStageChanged($recordId, $stageId, $fromOrderedIds, $toOrderedIds)
    {
        $this->skipRender();

        if (! $record = $this->resolve([$recordId])->first()) {
            return;
        }

        $from = $this->resolve($fromOrderedIds);
        $to = $this->resolve($toOrderedIds);

        foreach ($from->concat($to)->push($record) as $authorizable) {
            $this->authorize('update', $authorizable);
        }

        abort_unless($this->stages()->contains('id', $stageId), 404);

        $stageId = (int) $stageId;
        $fromStageId = $this->columnOf($record);

        DB::transaction(function () use ($record, $stageId, $fromStageId, $from, $to) {
            $record->update([
                'pipeline_stage_id' => $stageId,
            ]);

            // Both lists, because a record with no stage already shows in the first
            // column, so dropping it there makes them the same column.
            $placed = $from->concat($to);

            $this->reorder($from, $fromStageId, $placed);
            $this->reorder($to, $stageId, $placed);
        });

        $this->stageLimits[$stageId] = $this->stageLimit($stageId) + 1;

        if ($fromStageId !== null) {
            $this->stageLimits[$fromStageId] = max(0, $this->stageLimit($fromStageId) - 1);
        }

        $summaries = $this->columnSummaries();

        $this->dispatch('board-columns-updated', columns: collect([$fromStageId, $stageId])
            ->filter()
            ->unique()
            ->map(fn (int $id) => ['stage' => $id, ...$summaries[$id]])
            ->values()
            ->all());
    }

    /**
     * Resolve the ids the board sent us, dropping anything that is not a record.
     *
     * A stage container holds more than the record cards -- each card is followed by its
     * delete-confirm dialog -- so a host still serving a stale published copy of
     * kanban-board/sortable.blade.php harvests ids like 'modalDeleteLead4' alongside the
     * real ones. Those have to be skipped rather than dereferenced, and they must not
     * consume an order number either.
     */
    protected function resolve(array $orderedIds): Collection
    {
        $ids = collect($orderedIds)
            ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))
            ->filter()
            ->values();

        $records = $this->boardModel()::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn (int $id) => $records->get($id))->filter()->values();
    }

    /**
     * The column a record shows in.
     */
    protected function columnOf(Model $record): ?int
    {
        return $this->stages()->contains('id', $record->pipeline_stage_id)
            ? $record->pipeline_stage_id
            : $this->firstStageId();
    }

    /**
     * Renumber the cards the user can see contiguously from 1, then make sure the
     * cards they can't see stay behind them.
     *
     * NULL sorts first, so on a column that has never been ordered, numbering only the
     * loaded cards would send them behind everything not loaded yet. One statement keeps
     * those cards' relative order and leaves their updated_at alone, because nobody
     * edited them. Callers authorize the whole batch first, so a denial cannot leave the
     * stage half renumbered.
     */
    protected function reorder(Collection $loaded, ?int $stageId, Collection $placed): void
    {
        $loaded->each(fn (Model $record, int $index) => $record->update([
            'pipeline_stage_order' => $index + 1,
        ]));

        if ($stageId === null) {
            return;
        }

        $count = $loaded->count();
        $order = $this->column('pipeline_stage_order');

        $peers = fn () => $this->constrainToStage($this->boardQuery(), $stageId)
            ->whereNotIn($this->column('id'), $placed->pluck('id'));

        $inTheWay = $peers()
            ->where(fn (Builder $query) => $query->whereNull($order)->orWhere($order, '<=', $count))
            ->exists();

        if ($inTheWay) {
            $peers()->toBase()->update([
                'pipeline_stage_order' => DB::raw('COALESCE('.$order.', 0) + '.($count + 1)),
            ]);
        }
    }

    public function render()
    {
        $summaries = $this->columnSummaries();

        $pages = $this->stages()
            ->mapWithKeys(fn ($stage) => [$stage->id => $this->pageIds($stage->id, 0, $this->stageLimit($stage->id))])
            ->toBase();

        $cards = $this->cards($pages->flatten()->all());

        $stages = $this->stages()
            ->toBase()
            ->map(fn ($stage) => [
                'id' => $stage->id,
                'name' => $stage->name,
                'group' => $this->id(),
                'stageRecordsId' => "{$this->id()}-{$stage->id}",
                'records' => collect($pages[$stage->id])->map(fn (int $id) => $cards->get($id))->filter(),
                ...$summaries[$stage->id],
            ]);

        $this->dispatch('board-loaded');

        return view('laravel-crm::livewire.'.Str::plural($this->model).'.'.$this->model.'-board', [
            'users' => $this->users,
            'labels' => $this->labels(),
            'filterCount' => $this->filterCount(),
            'stages' => $stages,
        ]);
    }
}
