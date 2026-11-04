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

    expect($board->get('stageLimits'))->not->toBeEmpty();

    $board->set('search', 'Needle');

    expect($board->get('stageLimits'))->toBe([])
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

    expect($board->get('stageLimits'))->toBe([])
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
