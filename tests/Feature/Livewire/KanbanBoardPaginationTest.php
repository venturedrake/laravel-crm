<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * The boards used to load every record, which on a CRM with tens of thousands of
 * records meant 74 MB of HTML and the odd 30-second timeout. Each column now
 * renders a page of cards and fetches the rest on demand, which takes three
 * things that were free when everything was on screen and have to be earned now:
 *
 * - The column header reports the whole filtered set, not the page.
 * - A dragged order has to survive the cards that aren't loaded. pipeline_stage_order
 *   is NULL on most records and NULL sorts first, so numbering the loaded cards alone
 *   sends them behind everything else.
 * - A record with no stage still belongs in the first column, in its count too.
 */

const BOARD_PAGE = 5;

beforeEach(function () {
    config()->set('laravel-crm.board.per_column', BOARD_PAGE);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);

    foreach ([Lead::class, Deal::class, Quote::class] as $model) {
        $pipeline = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ]);

        foreach (['First', 'Second', 'Third'] as $order => $stage) {
            $pipeline->pipelineStages()->create([
                'external_id' => Str::uuid()->toString(),
                'name' => $stage,
                'order' => $order,
            ]);
        }
    }
});

dataset('boards', [
    'lead' => [LeadBoard::class, Lead::class, 'crm leads', 'amount'],
    'deal' => [DealBoard::class, Deal::class, 'crm deals', 'amount'],
    'quote' => [QuoteBoard::class, Quote::class, 'crm quotes', 'total'],
]);

function boardStages(string $model)
{
    return Pipeline::where('model', $model)->first()->pipelineStages()->orderBy('order')->get();
}

function boardUser(string $permission, bool $edit = true): void
{
    test()->actingAsUserWithPermissions(array_filter(["view {$permission}", $edit ? "edit {$permission}" : null]));
}

/**
 * Insert cards directly, skipping the observers. Every row shares one created_at so
 * that only the id can settle the order, and the order stays NULL like the real thing.
 *
 * @return array<int, int> ids, oldest first
 */
function boardSeed(string $model, ?int $stageId, int $count, array $attributes = []): array
{
    $instance = new $model;
    $now = now()->startOfSecond()->toDateTimeString();
    $ids = [];

    for ($i = 0; $i < $count; $i++) {
        $ids[] = DB::table($instance->getTable())->insertGetId(array_merge([
            'external_id' => Str::uuid()->toString(),
            'title' => 'Card '.$i,
            'pipeline_stage_id' => $stageId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }

    return $ids;
}

function boardColumn(Testable $board, int $stageId): array
{
    return collect($board->viewData('stages'))->firstWhere('id', $stageId);
}

function boardCardIds(Testable $board, int $stageId): array
{
    return boardColumn($board, $stageId)['records']->pluck('id')->values()->all();
}

function boardDispatch(Testable $board, string $event): array
{
    return collect($board->effects['dispatches'] ?? [])->firstWhere('name', $event)['params'];
}

/**
 * Load the next page and return the card ids in it, in order.
 */
function boardLoadMore(Testable $board, int $stageId): array
{
    $board->call('loadMore', $stageId);

    preg_match_all('/data-record-id="(\d+)"/', boardDispatch($board, 'board-column-loaded')['html'], $matches);

    return array_map('intval', $matches[1]);
}

/**
 * Walk a column to its end, returning every card id the way a user would meet them.
 */
function boardWalk(Testable $board, int $stageId): array
{
    $ids = boardCardIds($board, $stageId);

    while (boardHasMore($board, $stageId)) {
        $ids = array_merge($ids, boardLoadMore($board, $stageId));
    }

    return $ids;
}

function boardHasMore(Testable $board, int $stageId): bool
{
    $loaded = collect($board->effects['dispatches'] ?? [])->firstWhere('name', 'board-column-loaded');

    return $loaded ? $loaded['params']['hasMore'] : boardColumn($board, $stageId)['hasMore'];
}

function boardQueryCount(Closure $callback): int
{
    $count = 0;

    DB::listen(function () use (&$count) {
        $count++;
    });

    $before = $count;

    $callback();

    return $count - $before;
}

/**
 * Put the database behind a connection prefix the way a host's config would. Testbench
 * builds the schema before a test can change the config, so the tables are renamed.
 */
function boardPrefixTables(string $prefix): void
{
    $connection = DB::connection();

    foreach ($connection->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'") as $table) {
        $connection->statement("alter table \"{$table->name}\" rename to \"{$prefix}{$table->name}\"");
    }

    $connection->setTablePrefix($prefix);
}

it('renders one page per column and says when more remain', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second, $third] = boardStages($model);

    boardSeed($model, $first->id, BOARD_PAGE + 3);
    boardSeed($model, $second->id, BOARD_PAGE);

    $board = Livewire::test($component);

    expect(boardCardIds($board, $first->id))->toHaveCount(BOARD_PAGE)
        ->and(boardColumn($board, $first->id)['hasMore'])->toBeTrue()
        ->and(boardCardIds($board, $second->id))->toHaveCount(BOARD_PAGE)
        ->and(boardColumn($board, $second->id)['hasMore'])->toBeFalse()
        ->and(boardCardIds($board, $third->id))->toBeEmpty()
        ->and(boardColumn($board, $third->id)['hasMore'])->toBeFalse();

    expect(substr_count($board->html(), 'data-record-id='))->toBe(BOARD_PAGE * 2);
})->with('boards');

it('reads the page size from config', function (string $component, string $model, string $permission) {
    config()->set('laravel-crm.board.per_column', 2);
    boardUser($permission);
    $first = boardStages($model)->first();

    boardSeed($model, $first->id, 6);

    $board = Livewire::test($component);

    expect(boardCardIds($board, $first->id))->toHaveCount(2)
        ->and(boardLoadMore($board, $first->id))->toHaveCount(2);
})->with('boards');

it('walks a column end to end without repeats even when created_at ties', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $ids = boardSeed($model, $first->id, BOARD_PAGE * 3 + 2);

    expect(boardWalk(Livewire::test($component), $first->id))->toBe($ids);
})->with('boards');

// SQLite happens to return ties in rowid order, so the walk above passes without the id
// in the sort. MySQL promises nothing, which is what repeats a card across pages.
it('settles ties on the id', function (string $component, string $model, string $permission) {
    boardUser($permission);
    boardSeed($model, boardStages($model)->first()->id, 2);

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    Livewire::test($component);

    $table = (new $model)->getTable();

    expect(collect($queries)->contains(fn ($sql) => str_ends_with(
        preg_replace('/ limit .*$/', '', $sql),
        sprintf('order by "%1$s"."pipeline_stage_order" asc, "%1$s"."created_at" asc, "%1$s"."id" asc', $table),
    )))->toBeTrue();
})->with('boards');

it('renders every loaded card again on a full render', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $ids = boardSeed($model, $first->id, BOARD_PAGE * 2 + 1);

    $board = Livewire::test($component);
    boardLoadMore($board, $first->id);

    $board->call('$refresh');

    expect(boardCardIds($board, $first->id))->toBe(array_slice($ids, 0, BOARD_PAGE * 2));
})->with('boards');

it('counts and totals the whole column, not the page', function (string $component, string $model, string $permission, string $amount) {
    boardUser($permission);
    $first = boardStages($model)->first();

    boardSeed($model, $first->id, BOARD_PAGE + 2, [$amount => 12345, 'currency' => 'USD']);

    $column = boardColumn(Livewire::test($component), $first->id);

    expect($column['count'])->toBe(BOARD_PAGE + 2)
        ->and($column['totals'])->toBe([(string) money(12345 * (BOARD_PAGE + 2), 'USD')]);
})->with('boards');

it('keeps currencies apart rather than adding them up', function (string $component, string $model, string $permission, string $amount) {
    boardUser($permission);
    $first = boardStages($model)->first();

    boardSeed($model, $first->id, 2, [$amount => 1000, 'currency' => 'USD']);
    boardSeed($model, $first->id, 1, [$amount => 500, 'currency' => 'EUR']);

    expect(boardColumn(Livewire::test($component), $first->id)['totals'])
        ->toEqualCanonicalizing([(string) money(2000, 'USD'), (string) money(500, 'EUR')]);
})->with('boards');

it('shows the header count in the existing format with the total beneath it', function (string $component, string $model, string $permission, string $amount) {
    boardUser($permission);
    $first = boardStages($model)->first();

    boardSeed($model, $first->id, 2, [$amount => 1000, 'currency' => 'USD']);

    Livewire::test($component)
        ->assertSeeHtml('<span>First (<span data-board-count>2</span>)</span>')
        ->assertSeeHtml('<div>'.money(2000, 'USD').'</div>');
})->with('boards');

it('adds a card to the summaries of both columns when it moves', function (string $component, string $model, string $permission, string $amount) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $moved = boardSeed($model, $first->id, 1, [$amount => 1000, 'currency' => 'USD'])[0];
    $stayed = boardSeed($model, $first->id, 1, [$amount => 300, 'currency' => 'USD'])[0];
    $resident = boardSeed($model, $second->id, 1, [$amount => 50, 'currency' => 'USD'])[0];

    $board = Livewire::test($component)
        ->call('onStageChanged', $moved, $second->id, [$stayed], [$moved, $resident]);

    expect($model::find($moved)->pipeline_stage_id)->toBe($second->id)
        ->and(boardDispatch($board, 'board-columns-updated')['columns'])->toBe([
            ['stage' => $first->id, 'count' => 1, 'totals' => [(string) money(300, 'USD')], 'hasMore' => false],
            ['stage' => $second->id, 'count' => 2, 'totals' => [(string) money(1050, 'USD')], 'hasMore' => false],
        ]);
})->with('boards');

it('does not resend the board after a move', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $ids = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component)
        ->call('onStageChanged', $ids[0], $second->id, [$ids[1]], [$ids[0]]);

    expect($board->effects['html'] ?? null)->toBeNull();
})->with('boards');

it('keeps a dropped card where it landed in a partly loaded column of unordered cards', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $moved = boardSeed($model, $first->id, 1)[0];
    $residents = boardSeed($model, $second->id, BOARD_PAGE * 2 + 3);
    $loaded = array_slice($residents, 0, BOARD_PAGE);
    $unloaded = array_slice($residents, BOARD_PAGE);

    $board = Livewire::test($component);

    $to = $loaded;
    array_splice($to, 2, 0, [$moved]);

    $board->call('onStageChanged', $moved, $second->id, [], $to);

    // Every record, in the order the user is looking at, then the rest as they were.
    expect(boardWalk(Livewire::test($component), $second->id))->toBe([...$to, ...$unloaded]);

    // The same instance, carrying its limits: the next page starts straight after.
    expect(boardLoadMore($board, $second->id))->toBe(array_slice($unloaded, 0, BOARD_PAGE));
})->with('boards');

it('keeps the unloaded order of the column a card left', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $ids = boardSeed($model, $first->id, BOARD_PAGE + 4);

    $board = Livewire::test($component);

    $board->call('onStageChanged', $ids[1], $second->id, [$ids[0], ...array_slice($ids, 2, BOARD_PAGE - 2)], [$ids[1]]);

    expect(boardLoadMore($board, $first->id))->toBe(array_slice($ids, BOARD_PAGE, BOARD_PAGE))
        ->and(boardDispatch($board, 'board-column-loaded')['hasMore'])->toBeFalse();
})->with('boards');

// A short column's limit used to be the page size, not the cards it showed, so counting
// from the limit skipped as many cards as the column was short of a page.
it('starts the next page after the cards on screen when a card lands in a column that grew unseen', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $moved = boardSeed($model, $first->id, 1)[0];
    $shown = boardSeed($model, $second->id, 2);

    $board = Livewire::test($component);

    $unseen = boardSeed($model, $second->id, BOARD_PAGE + 3);

    $board->call('onStageChanged', $moved, $second->id, [], [$moved, ...$shown]);

    expect($board->get('stageLimits')[$second->id])->toBe(3)
        ->and(boardLoadMore($board, $second->id))->toBe(array_slice($unseen, 0, BOARD_PAGE));
})->with('boards');

it('starts the next page after the cards on screen when a card leaves a column that grew unseen', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $stayed, $alsoStayed] = boardSeed($model, $first->id, 3);

    $board = Livewire::test($component);

    $unseen = boardSeed($model, $first->id, BOARD_PAGE + 3);

    $board->call('onStageChanged', $moved, $second->id, [$stayed, $alsoStayed], [$moved]);

    expect($board->get('stageLimits')[$first->id])->toBe(2)
        ->and(boardLoadMore($board, $first->id))->toBe(array_slice($unseen, 0, BOARD_PAGE));
})->with('boards');

it('offers load more when a column grew unseen by less than the page it had room for', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $moved = boardSeed($model, $first->id, 1)[0];
    $shown = boardSeed($model, $second->id, 1);

    $board = Livewire::test($component);

    boardSeed($model, $second->id, 1);

    $board->call('onStageChanged', $moved, $second->id, [], [$moved, ...$shown]);

    $column = collect(boardDispatch($board, 'board-columns-updated')['columns'])->firstWhere('stage', $second->id);

    expect($column['count'])->toBe(3)
        ->and($column['hasMore'])->toBeTrue();
})->with('boards');

// The lists are read at the drop, and the call waits behind a Load more already in flight.
it('counts the page that landed while a drag was waiting its turn', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $ids = boardSeed($model, $first->id, BOARD_PAGE * 3 + 1);
    [$kept, $moved] = $ids;
    $listedAtTheDrop = [$kept, ...array_slice($ids, 2, BOARD_PAGE - 2)];

    $board = Livewire::test($component);
    boardLoadMore($board, $first->id);

    $board->call('onStageChanged', $moved, $second->id, $listedAtTheDrop, [$moved]);

    expect($board->get('stageLimits')[$first->id])->toBe(BOARD_PAGE * 2 - 1)
        ->and(boardLoadMore($board, $first->id))->toBe(array_slice($ids, BOARD_PAGE * 2, BOARD_PAGE));
})->with('boards');

it('shows a card that lands in a column a drag emptied', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $only = boardSeed($model, $first->id, 1)[0];

    $board = Livewire::test($component)->call('onStageChanged', $only, $second->id, [], [$only]);

    $landed = boardSeed($model, $first->id, 1)[0];

    expect(boardCardIds($board->call('$refresh'), $first->id))->toBe([$landed])
        ->and(boardColumn($board, $first->id)['hasMore'])->toBeFalse();
})->with('boards');

it('keeps showing every card of a short column a drag touched', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $moved = boardSeed($model, $first->id, 1)[0];
    $resident = boardSeed($model, $second->id, 1)[0];

    $board = Livewire::test($component)->call('onStageChanged', $moved, $second->id, [], [$moved, $resident]);

    $landed = boardSeed($model, $second->id, 1)[0];

    expect(boardCardIds($board->call('$refresh'), $second->id))->toEqualCanonicalizing([$moved, $resident, $landed]);
})->with('boards');

it('shows the deal a menu action sends to a column a drag emptied', function () {
    boardUser('crm deals');
    $pipeline = Pipeline::where('model', Deal::class)->first();
    $won = $pipeline->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Closed Won', 'order' => 9]);
    $first = boardStages(Deal::class)->first();

    $dragged = boardSeed(Deal::class, $won->id, 1)[0];
    $open = boardSeed(Deal::class, $first->id, 1)[0];

    $board = Livewire::test(DealBoard::class)
        ->call('onStageChanged', $dragged, $first->id, [], [$dragged, $open])
        ->call('won', $open);

    expect(boardCardIds($board, $won->id))->toBe([$open]);
});

it('numbers the column a card was dragged from when it had already moved elsewhere', function (string $component, string $model, string $permission, string $amount, Closure $source) {
    boardUser($permission);
    [$first, $second, $third] = boardStages($model);

    $ids = boardSeed($model, $first->id, BOARD_PAGE + 3);
    $moved = $ids[0];
    $left = array_slice($ids, 1, BOARD_PAGE - 1);
    $unloaded = array_slice($ids, BOARD_PAGE);

    $board = Livewire::test($component);

    // Another session, through something that isn't the board.
    DB::table((new $model)->getTable())->where('id', $moved)->update(['pipeline_stage_id' => $third->id]);

    $board->call('onStageChanged', $moved, $second->id, $left, [$moved], ...$source($first->id));

    expect(boardDispatch($board, 'board-columns-updated')['columns'])->toContain(
        ['stage' => $first->id, 'count' => count($left) + count($unloaded), 'totals' => [], 'hasMore' => true],
    )
        ->and($board->get('stageLimits')[$third->id])->toBe(0)
        ->and(boardLoadMore($board, $first->id))->toBe($unloaded)
        ->and(boardWalk(Livewire::test($component), $first->id))->toBe([...$left, ...$unloaded]);
})->with('boards')->with([
    'sent by the client' => fn (int $stage) => [$stage],
    'sent as a string' => fn (int $stage) => [(string) $stage],
    'not sent, from a stale published view' => fn (int $stage) => [],
    'not a stage' => fn (int $stage) => ['nonsense'],
    'null' => fn (int $stage) => [null],
    'on another board' => fn (int $stage) => [999999],
]);

it('numbers the column being sorted when its first card had already moved elsewhere', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, , $third] = boardStages($model);

    $ids = boardSeed($model, $first->id, BOARD_PAGE + 2);
    $stale = $ids[0];
    $loaded = array_slice($ids, 1, BOARD_PAGE - 1);
    $unloaded = array_slice($ids, BOARD_PAGE);

    $board = Livewire::test($component);

    DB::table((new $model)->getTable())->where('id', $stale)->update(['pipeline_stage_id' => $third->id]);

    $reversed = array_reverse($loaded);

    $board->call('onStageSorted', [$stale, ...$reversed], $first->id);

    expect(boardWalk(Livewire::test($component), $first->id))->toBe([...$reversed, ...$unloaded]);
})->with('boards');

it('refuses a stage that only matches the board by loose comparison', function () {
    boardUser('crm deals');

    // Stage 1 is on the lead pipeline, and every id on this board == true.
    $record = boardSeed(Deal::class, boardStages(Deal::class)->first()->id, 1)[0];

    Livewire::test(DealBoard::class)
        ->call('onStageChanged', $record, true, [], [$record])
        ->assertNotFound();

    expect(Deal::find($record)->pipeline_stage_id)->toBe(boardStages(Deal::class)->first()->id);
});

it('resends the board when another call shares the request', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    boardSeed($model, $first->id, BOARD_PAGE + 1);
    $needle = boardSeed($model, $second->id, BOARD_PAGE + 1, ['title' => 'Needle'])[0];

    $call = fn (int $stageId) => ['method' => 'loadMore', 'params' => [$stageId], 'path' => ''];

    $board = Livewire::test($component)->update(calls: [$call($first->id), $call($second->id)]);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(collect($board->effects['dispatches'] ?? [])->pluck('name'))->not->toContain('board-column-loaded')
        ->and(boardCardIds($board, $first->id))->toHaveCount(BOARD_PAGE + 1)
        ->and(boardCardIds($board, $second->id))->toContain($needle)->toHaveCount(BOARD_PAGE + 1);
})->with('boards');

it('resends the board when a filter shares the request with load more', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    boardSeed($model, $first->id, BOARD_PAGE * 2);
    $hit = boardSeed($model, $second->id, 1, ['title' => 'Needle'])[0];

    $board = Livewire::test($component)->update(
        calls: [['method' => 'loadMore', 'params' => [$first->id], 'path' => '']],
        updates: ['search' => 'Needle'],
    );

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $first->id))->toBeEmpty()
        ->and(boardCardIds($board, $second->id))->toBe([$hit])
        ->and($board->get('stageLimits')[$first->id])->toBe(0);
})->with('boards');

it('shows a won deal when load more shares the request', function () {
    boardUser('crm deals');
    $pipeline = Pipeline::where('model', Deal::class)->first();
    $won = $pipeline->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Closed Won', 'order' => 9]);
    $first = boardStages(Deal::class)->first();

    $open = boardSeed(Deal::class, $first->id, BOARD_PAGE + 1)[0];

    $board = Livewire::test(DealBoard::class)->update(calls: [
        ['method' => 'loadMore', 'params' => [$first->id], 'path' => ''],
        ['method' => 'won', 'params' => [$open], 'path' => ''],
    ]);

    expect(boardCardIds($board, $won->id))->toBe([$open])
        ->and(boardCardIds($board, $first->id))->not->toContain($open)->toHaveCount(BOARD_PAGE)
        ->and(collect($board->effects['dispatches'] ?? [])->pluck('name'))->not->toContain('board-column-loaded');
});

it('resends the board when a render landed after the card was picked up', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $stayed] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component);
    $pickedUp = $board->get('renderCount');

    $board->call('$refresh');

    expect($board->get('renderCount'))->toBeGreaterThan($pickedUp);

    $board->call('onStageChanged', $moved, $second->id, [$stayed], [$moved], $first->id, $pickedUp);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $second->id))->toBe([$moved])
        ->and(boardCardIds($board, $first->id))->toBe([$stayed])
        ->and(collect($board->effects['dispatches'] ?? [])->pluck('name'))->not->toContain('board-columns-updated');
})->with('boards');

// What the client sends after a failed drag, when no render corrected the screen.
it('resends the board for the drag after a failed one', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $stayed] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component)
        ->call('onStageChanged', $moved, $second->id, [$stayed], [$moved], $first->id, -1);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $second->id))->toBe([$moved]);
})->with('boards');

// On Livewire 3 a failed drag's refresh shares a request with whatever was queued behind it.
it('resends the board for a refresh that shares a request with a card call', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $stayed] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component)->update(calls: [
        ['method' => 'onStageChanged', 'params' => [$moved, $second->id, [$stayed], [$moved], $first->id, null], 'path' => ''],
        ['method' => 'refreshBoard', 'params' => [], 'path' => ''],
    ]);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $second->id))->toBe([$moved]);
})->with('boards');

it('resends the board for a refresh on its own', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $board = Livewire::test($component);

    $card = boardSeed($model, $first->id, 1)[0];

    $board->call('refreshBoard');

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $first->id))->toBe([$card]);
})->with('boards');

it('watches for a failed drag only where cards can be dragged', function (string $component, string $model, string $permission) {
    boardUser($permission);

    Livewire::test($component)->assertSeeHtml('$wire.$hook(');
    Livewire::test($component, ['sortable' => false])->assertDontSeeHtml('$wire.$hook(');
})->with('boards');

it('resends the board when a render landed during a sort', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    [$one, $two] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component);
    $pickedUp = $board->get('renderCount');

    $board->call('$refresh')->call('onStageSorted', [$two, $one], $first->id, $pickedUp);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $first->id))->toBe([$two, $one]);
})->with('boards');

it('skips the render when no render landed since the card was picked up', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $stayed] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component);

    $board->call('onStageChanged', $moved, $second->id, [$stayed], [$moved], $first->id, (string) $board->get('renderCount'));

    expect($board->effects['html'] ?? null)->toBeNull();

    $board->call('onStageSorted', [$stayed], $first->id, $board->get('renderCount'));

    expect($board->effects['html'] ?? null)->toBeNull();
})->with('boards');

it('takes a deleted card off the board when it is dragged', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$deleted, $kept] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component);

    $model::find($deleted)->delete();

    $board->call('onStageChanged', $deleted, $second->id, [$kept], [$deleted], $first->id);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $second->id))->toBeEmpty()
        ->and(boardColumn($board, $first->id)['count'])->toBe(1);
})->with('boards');

it('saves a drag and clears the deleted cards listed with it', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $deleted, $kept] = boardSeed($model, $first->id, 3);

    $board = Livewire::test($component);

    $model::find($deleted)->delete();

    $board->call('onStageChanged', $moved, $second->id, [$deleted, $kept], [$moved], $first->id);

    expect($model::find($moved)->pipeline_stage_id)->toBe($second->id)
        ->and($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $first->id))->toBe([$kept])
        ->and(boardCardIds($board, $second->id))->toBe([$moved]);
})->with('boards');

it('takes a deleted card off the board when its column is sorted', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    [$deleted, $kept] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component);

    $model::find($deleted)->delete();

    $board->call('onStageSorted', [$kept, $deleted], $first->id);

    expect($board->effects['html'] ?? null)->not->toBeNull()
        ->and(boardCardIds($board, $first->id))->toBe([$kept]);
})->with('boards');

it('leaves a lead converted elsewhere where it is and takes it off the board', function () {
    boardUser('crm leads');
    [$first, $second] = boardStages(Lead::class);

    $lead = boardSeed(Lead::class, $first->id, 1)[0];

    $board = Livewire::test(LeadBoard::class);

    DB::table((new Lead)->getTable())->where('id', $lead)->update(['converted_at' => now()]);

    $board->call('onStageChanged', $lead, $second->id, [], [$lead], $first->id);

    expect(Lead::find($lead)->pipeline_stage_id)->toBe($first->id)
        ->and(boardCardIds($board, $second->id))->toBeEmpty();
});

it('does not resend the board for the ids a stale published view harvests', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    [$moved, $stayed] = boardSeed($model, $first->id, 2);

    $board = Livewire::test($component)
        ->call('onStageChanged', $moved, $second->id, ['modalDelete'.$stayed, $stayed], ['modalDelete'.$moved, $moved]);

    expect($board->effects['html'] ?? null)->toBeNull();
})->with('boards');

// A bound parameter per id runs into MySQL's limit of 65,535 a statement.
it('does not bind the ids of matching contacts', function (string $component, string $model, string $permission) {
    config()->set('laravel-crm.encrypt_db_fields', true);

    boardUser($permission);
    $first = boardStages($model)->first();

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $hit = boardSeed($model, $first->id, 1, ['person_id' => $person->id])[0];

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    $board = Livewire::test($component)->set('search', 'Jane');

    $inlined = sprintf('"%s"."person_id" in (%d)', (new $model)->getTable(), $person->id);

    expect(boardCardIds($board, $first->id))->toBe([$hit])
        ->and(collect($queries)->contains(fn ($sql) => str_contains($sql, $inlined)))->toBeTrue();
})->with('boards');

it('works on a connection with a table prefix', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $ids = boardSeed($model, $first->id, BOARD_PAGE + 1);
    $hit = boardSeed($model, $first->id, 1, ['person_id' => $person->id])[0];

    boardPrefixTables('app_');

    $board = Livewire::test($component);

    expect(boardColumn($board, $first->id)['count'])->toBe(BOARD_PAGE + 2);

    // The peers still in the way are what reaches the raw push-back.
    $board->call('onStageSorted', array_reverse(array_slice($ids, 0, BOARD_PAGE)));

    expect($model::find($hit)->pipeline_stage_order)->not->toBeNull()
        ->and(boardCardIds($board->set('search', 'Jane Smith'), $first->id))->toBe([$hit]);
})->with('boards');

it('reorders within a partly loaded column without disturbing the rest', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $ids = boardSeed($model, $first->id, BOARD_PAGE + 4);
    $loaded = array_slice($ids, 0, BOARD_PAGE);
    $unloaded = array_slice($ids, BOARD_PAGE);

    $board = Livewire::test($component);

    $reversed = array_reverse($loaded);

    $board->call('onStageSorted', $reversed);

    expect(boardWalk(Livewire::test($component), $first->id))->toBe([...$reversed, ...$unloaded]);
})->with('boards');

it('leaves unloaded peers untouched once they are already behind the loaded cards', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $ids = boardSeed($model, $first->id, BOARD_PAGE + 2, ['updated_at' => '2020-01-01 00:00:00']);
    $loaded = array_slice($ids, 0, BOARD_PAGE);

    Livewire::test($component)->call('onStageSorted', array_reverse($loaded));

    $peer = $model::find(end($ids));

    // Pushed once, and only the order: nobody edited this record.
    expect($peer->pipeline_stage_order)->toBe(BOARD_PAGE + 1)
        ->and($peer->updated_at->year)->toBe(2020);

    Livewire::test($component)->call('onStageSorted', $loaded);

    expect($model::find(end($ids))->pipeline_stage_order)->toBe(BOARD_PAGE + 1);
})->with('boards');

it('pushes back only the cards in the column', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $ids = boardSeed($model, $first->id, BOARD_PAGE + 1);
    $elsewhere = boardSeed($model, $second->id, 1)[0];

    Livewire::test($component)->call('onStageSorted', array_slice($ids, 0, BOARD_PAGE));

    expect($model::find($elsewhere)->pipeline_stage_order)->toBeNull();
})->with('boards');

it('shows a record with no stage in the first column and its count', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $orphan = boardSeed($model, null, 1, ['currency' => 'USD'])[0];
    boardSeed($model, $second->id, 1);

    $board = Livewire::test($component);

    expect(boardCardIds($board, $first->id))->toBe([$orphan])
        ->and(boardColumn($board, $first->id)['count'])->toBe(1);
})->with('boards');

it('shows a record whose stage was deleted in the first column and its count', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, , $third] = boardStages($model);

    $orphan = boardSeed($model, $third->id, 1, ['currency' => 'USD'])[0];
    $third->delete();

    $board = Livewire::test($component);

    expect(boardCardIds($board, $first->id))->toBe([$orphan])
        ->and(boardColumn($board, $first->id)['count'])->toBe(1);
})->with('boards');

it('keeps a record on another pipeline off the board and out of the counts', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();

    $otherPipeline = Pipeline::create(['external_id' => Str::uuid()->toString(), 'name' => 'Other', 'model' => 'Other'])
        ->pipelineStages()
        ->create(['external_id' => Str::uuid()->toString(), 'name' => 'Elsewhere', 'order' => 0]);

    boardSeed($model, $otherPipeline->id, 1, ['currency' => 'USD']);

    $board = Livewire::test($component);

    expect(boardCardIds($board, $first->id))->toBeEmpty()
        ->and(boardColumn($board, $first->id)['count'])->toBe(0);
})->with('boards');

it('searches across columns and starts every column again', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $ids = boardSeed($model, $first->id, BOARD_PAGE * 2 + 1);
    $hit = boardSeed($model, $second->id, 1, ['title' => 'Needle'])[0];

    $board = Livewire::test($component);
    boardLoadMore($board, $first->id);

    expect($board->get('stageLimits')[$first->id])->toBe(BOARD_PAGE * 2);

    $board->set('search', 'Needle');

    expect($board->get('stageLimits')[$first->id])->toBe(0)
        ->and(boardCardIds($board, $first->id))->toBeEmpty()
        ->and(boardColumn($board, $first->id)['count'])->toBe(0)
        ->and(boardCardIds($board, $second->id))->toBe([$hit]);

    $board->set('search', '');

    expect(boardCardIds($board, $first->id))->toBe(array_slice($ids, 0, BOARD_PAGE));
})->with('boards');

it('filters by owner and label across columns and starts every column again', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    boardSeed($model, $first->id, BOARD_PAGE + 1, ['user_owner_id' => 1]);
    $owned = boardSeed($model, $second->id, 1, ['user_owner_id' => 2])[0];

    $board = Livewire::test($component);
    boardLoadMore($board, $first->id);

    $board->set('user_id', [2]);

    expect($board->get('stageLimits')[$first->id])->toBe(0)
        ->and(boardCardIds($board, $first->id))->toBeEmpty()
        ->and(boardCardIds($board, $second->id))->toBe([$owned]);

    $label = Label::create(['external_id' => Str::uuid()->toString(), 'name' => 'Hot', 'hex' => 'ff0000']);
    $model::find($owned)->labels()->attach($label);

    $board->set('user_id', [])->set('label_id', [$label->id]);

    expect(boardCardIds($board, $second->id))->toBe([$owned])
        ->and(boardColumn($board, $second->id)['count'])->toBe(1)
        ->and(boardColumn($board, $first->id)['count'])->toBe(0);
})->with('boards');

it('searches contact names with encryption off', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first] = boardStages($model);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $organization = Organization::create(['name' => 'Cedar Point']);

    $byPerson = boardSeed($model, $first->id, 1, ['person_id' => $person->id])[0];
    $byOrganization = boardSeed($model, $first->id, 1, ['organization_id' => $organization->id])[0];
    boardSeed($model, $first->id, 1);

    expect(boardCardIds(Livewire::test($component)->set('search', 'Jane Smith'), $first->id))->toBe([$byPerson])
        ->and(boardCardIds(Livewire::test($component)->set('search', 'Cedar'), $first->id))->toBe([$byOrganization]);
})->with('boards');

it('searches contact names with encryption on', function (string $component, string $model, string $permission) {
    config()->set('laravel-crm.encrypt_db_fields', true);

    boardUser($permission);
    [$first] = boardStages($model);

    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $organization = Organization::create(['name' => 'Cedar Point']);

    // What makes SQL LIKE useless here: the stored value is ciphertext.
    expect(DB::table($person->getTable())->value('first_name'))->not->toBe('Jane');

    $byPerson = boardSeed($model, $first->id, 1, ['person_id' => $person->id])[0];
    $byOrganization = boardSeed($model, $first->id, 1, ['organization_id' => $organization->id])[0];
    boardSeed($model, $first->id, 1);

    $board = Livewire::test($component)->set('search', 'Jane');

    expect(boardCardIds($board, $first->id))->toBe([$byPerson])
        ->and(boardColumn($board, $first->id)['count'])->toBe(1)
        ->and(boardCardIds(Livewire::test($component)->set('search', 'Cedar'), $first->id))->toBe([$byOrganization]);
})->with('boards');

it('does not scale its queries with the number of records', function (string $component, string $model, string $permission, string $amount) {
    boardUser($permission);
    [$first, $second, $third] = boardStages($model);

    $seed = function (int $perStage) use ($model, $first, $second, $third, $amount) {
        foreach ([$first, $second, $third] as $stage) {
            boardSeed($model, $stage->id, $perStage, [$amount => 1000, 'currency' => 'USD']);
        }
    };

    $seed(17);

    // The first render in a process also warms the settings and permission caches.
    Livewire::test($component);

    $small = boardQueryCount(fn () => Livewire::test($component));
    $smallBoard = Livewire::test($component);
    $smallMore = boardQueryCount(fn () => $smallBoard->call('loadMore', $first->id));

    $seed(150);

    $large = boardQueryCount(fn () => Livewire::test($component));
    $largeBoard = Livewire::test($component);
    $largeMore = boardQueryCount(fn () => $largeBoard->call('loadMore', $first->id));

    expect($large)->toBe($small)
        ->and($largeMore)->toBe($smallMore);
})->with('boards');

it('forbids loading more without the view permission', function (string $component, string $model, string $permission) {
    boardUser($permission);
    $first = boardStages($model)->first();
    boardSeed($model, $first->id, BOARD_PAGE + 1);

    $board = Livewire::test($component);

    test()->actingAsUserWithPermissions([]);

    $board->call('loadMore', $first->id)->assertForbidden();
})->with('boards');

it('loads more for a user who cannot edit', function (string $component, string $model, string $permission) {
    boardUser($permission, edit: false);
    $first = boardStages($model)->first();
    boardSeed($model, $first->id, BOARD_PAGE + 1);

    expect(boardLoadMore(Livewire::test($component, ['sortable' => false]), $first->id))->toHaveCount(1);
})->with('boards');

it('wires load more without the sortable script', function (string $component, string $model, string $permission) {
    boardUser($permission, edit: false);
    $first = boardStages($model)->first();
    boardSeed($model, $first->id, BOARD_PAGE + 1);

    Livewire::test($component, ['sortable' => false])
        ->assertSeeHtml('data-board-more')
        ->assertSeeHtml('wire:target="loadMore('.$first->id.')"')
        ->assertSeeHtml('board-column-loaded')
        ->assertDontSeeHtml('Sortable.get');
})->with('boards');

it('refuses to load a stage that is not on the board', function (string $component, string $model, string $permission) {
    boardUser($permission);

    $otherStage = PipelineStage::create(['external_id' => Str::uuid()->toString(), 'name' => 'Elsewhere', 'pipeline_id' => 999]);

    Livewire::test($component)->call('loadMore', $otherStage->id)->assertNotFound();
})->with('boards');

it('refuses to move a card to a stage that is not on the board', function (string $component, string $model, string $permission) {
    boardUser($permission);

    $record = boardSeed($model, boardStages($model)->first()->id, 1)[0];
    $otherStage = PipelineStage::create(['external_id' => Str::uuid()->toString(), 'name' => 'Elsewhere', 'pipeline_id' => 999]);

    Livewire::test($component)
        ->call('onStageChanged', $record, $otherStage->id, [], [$record])
        ->assertNotFound();

    expect($model::find($record)->pipeline_stage_id)->not->toBe($otherStage->id);
})->with('boards');

it('does not let the client set how many cards a column shows', function (string $component) {
    boardUser('crm leads');

    Livewire::test($component)->set('stageLimits', [1 => 100000]);
})->with('boards')->throws(CannotUpdateLockedPropertyException::class);

it('renders a board with no pipeline rather than failing', function (string $component, string $model, string $permission) {
    boardUser($permission);

    Pipeline::where('model', $model)->delete();

    Livewire::test($component)->assertOk();
})->with('boards');

it('keeps the owner filter capped on the boards', function (string $component, string $model, string $permission) {
    boardUser($permission);

    $class = get_class(auth()->user());

    foreach (range(1, 30) as $i) {
        $class::create(['name' => 'Owner '.$i, 'email' => "owner{$i}@example.com", 'password' => 'secret']);
    }

    expect(Livewire::test($component)->viewData('users'))->toHaveCount(20);
})->with('boards');

it('asks once for whether a page of quotes has orders', function () {
    boardUser('crm quotes');
    $first = boardStages(Quote::class)->first();

    boardSeed(Quote::class, $first->id, BOARD_PAGE);

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    Livewire::test(QuoteBoard::class);

    expect(collect($queries)->filter(fn ($sql) => str_contains($sql, 'crm_orders'))->count())->toBeLessThanOrEqual(1);
});

it('offers each quote card the actions for its state without per-card queries', function () {
    boardUser('crm quotes');
    $first = boardStages(Quote::class)->first();

    $open = Quote::create(['title' => 'Open', 'pipeline_stage_id' => $first->id, 'currency' => 'USD']);
    $accepted = Quote::create(['title' => 'Accepted', 'pipeline_stage_id' => $first->id, 'currency' => 'USD', 'accepted_at' => now()]);

    Livewire::test(QuoteBoard::class)
        ->assertSeeHtml("accept({$open->id})")
        ->assertSeeHtml("unaccept({$accepted->id})")
        ->assertSeeHtml("\$dispatch('quote-send', { id: {$open->id} })")
        ->assertDontSeeHtml("send({$open->id})");
});

it('offers another order only while an ordered quote still has quantity left', function (float $ordered, bool $offered) {
    boardUser('crm quotes');
    $first = boardStages(Quote::class)->first();

    $product = Product::create(['name' => 'Widget']);

    $quote = Quote::create([
        'title' => 'Ordered',
        'pipeline_stage_id' => $first->id,
        'currency' => 'USD',
        'subtotal' => 20,
        'total' => 20,
        'accepted_at' => now(),
    ]);

    $line = $quote->quoteProducts()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 10, 'amount' => 20]);

    Order::create(['quote_id' => $quote->id, 'currency' => 'USD'])
        ->orderProducts()
        ->create(['product_id' => $product->id, 'quote_product_id' => $line->id, 'quantity' => $ordered, 'price' => 10, 'amount' => 10]);

    $board = Livewire::test(QuoteBoard::class)
        ->assertDontSeeHtml("unaccept({$quote->id})");

    expect(str_contains($board->html(), route('laravel-crm.orders.create')))->toBe($offered);
})->with([
    'part ordered' => [1, true],
    'fully ordered' => [2, false],
]);

it('marks a lost deal for reopening and an open one as closable', function () {
    boardUser('crm deals');
    $first = boardStages(Deal::class)->first();

    $open = Deal::create(['title' => 'Open', 'pipeline_stage_id' => $first->id]);
    $closed = Deal::create(['title' => 'Closed', 'pipeline_stage_id' => $first->id, 'closed_at' => now()]);

    Livewire::test(DealBoard::class)
        ->assertSeeHtml("won({$open->id})")
        ->assertSeeHtml("reopen({$closed->id})");
});

it('leaves converted leads off the board and out of the counts', function () {
    boardUser('crm leads');
    $first = boardStages(Lead::class)->first();

    boardSeed(Lead::class, $first->id, 1, ['converted_at' => now()]);
    $open = boardSeed(Lead::class, $first->id, 1)[0];

    $board = Livewire::test(LeadBoard::class);

    expect(boardCardIds($board, $first->id))->toBe([$open])
        ->and(boardColumn($board, $first->id)['count'])->toBe(1);
});

it('resets a filtered board to every card', function (string $component, string $model, string $permission, string $amount) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $label = Label::create(['name' => 'Hot', 'hex' => 'ff0000']);
    $owned = boardSeed($model, $first->id, 2, ['title' => 'Needle', 'user_owner_id' => auth()->id(), $amount => 100, 'currency' => 'USD']);
    boardSeed($model, $first->id, 1, ['title' => 'Other', $amount => 200, 'currency' => 'USD']);
    boardSeed($model, $second->id, 3, ['title' => 'Other', $amount => 300, 'currency' => 'USD']);

    foreach ($owned as $id) {
        $model::find($id)->labels()->attach($label);
    }

    $unfiltered = collect(Livewire::test($component)->viewData('stages'))
        ->map(fn (array $stage) => [$stage['count'], $stage['totals']])
        ->all();

    $board = Livewire::test($component)
        ->set('search', 'Needle')
        ->set('user_id', [auth()->id()])
        ->set('label_id', [$label->id])
        ->set('userSearch', 'someone')
        ->set('showFilters', true);

    expect(boardColumn($board, $first->id)['count'])->toBe(2)
        ->and(boardColumn($board, $second->id)['count'])->toBe(0)
        ->and($board->viewData('filterCount'))->toBe(2);

    $board->call('clear')
        ->assertOk()
        ->assertSet('search', '')
        ->assertSet('user_id', [])
        ->assertSet('label_id', [])
        ->assertSet('userSearch', '')
        ->assertSet('showFilters', false);

    expect(collect($board->viewData('stages'))->map(fn (array $stage) => [$stage['count'], $stage['totals']])->all())->toBe($unfiltered)
        ->and(boardColumn($board, $first->id)['count'])->toBe(3)
        ->and(boardColumn($board, $second->id)['count'])->toBe(3)
        ->and($board->viewData('filterCount'))->toBe(0);
})->with('boards');

it('sends every column back to its first page on reset', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $inFirst = boardSeed($model, $first->id, BOARD_PAGE * 2);
    $inSecond = boardSeed($model, $second->id, BOARD_PAGE * 2);

    $board = Livewire::test($component)->set('search', 'Card');

    boardLoadMore($board, $first->id);
    boardLoadMore($board, $second->id);

    expect($board->get('stageLimits'))->toMatchArray([
        $first->id => BOARD_PAGE * 2,
        $second->id => BOARD_PAGE * 2,
    ]);

    $board->call('clear');

    expect($board->get('stageLimits'))->toMatchArray([
        $first->id => BOARD_PAGE,
        $second->id => BOARD_PAGE,
    ])
        ->and(boardCardIds($board, $first->id))->toBe(array_slice($inFirst, 0, BOARD_PAGE))
        ->and(boardCardIds($board, $second->id))->toBe(array_slice($inSecond, 0, BOARD_PAGE))
        ->and(boardColumn($board, $first->id)['hasMore'])->toBeTrue()
        ->and(boardColumn($board, $first->id)['count'])->toBe(BOARD_PAGE * 2);

    // The next page follows the first, rather than repeating what was loaded before.
    expect(boardLoadMore($board, $first->id))->toBe(array_slice($inFirst, BOARD_PAGE));
})->with('boards');

it('leaves a reset board able to drag', function (string $component, string $model, string $permission) {
    boardUser($permission);
    [$first, $second] = boardStages($model);

    $card = boardSeed($model, $first->id, 1)[0];

    $board = Livewire::test($component, ['sortable' => true, 'sortableBetweenStages' => false])
        ->set('search', 'Card');

    $renders = $board->get('renderCount');

    $board->call('clear')
        ->assertSet('sortable', true)
        ->assertSet('sortableBetweenStages', false)
        ->assertSet('layout', 'board');

    // The count a drag is checked against only ever goes up, so a client that picked a
    // card up before the reset can't be mistaken for one that saw the latest render.
    expect($board->get('renderCount'))->toBe($renders + 1);

    $board->call('onStageChanged', $card, $second->id, [], [$card], $first->id, $board->get('renderCount'));

    expect($model::find($card)->pipeline_stage_id)->toBe($second->id);
})->with('boards');
