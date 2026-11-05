<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

/*
 * Paging moved the shared stage and card views onto keys only PipelineBoard supplies, so
 * a host's own board, which has none of them and worked before, fell over. These are the
 * smallest boards a host can write: KanbanBoard has no view of its own that shows cards.
 */

class HostBoard extends KanbanBoard
{
    public $model = 'lead';

    public static array $cards = [];

    public function stages(): Collection
    {
        return collect([['id' => 1, 'name' => 'Open'], ['id' => 2, 'name' => 'Closed']]);
    }

    public function records(): Collection
    {
        return collect(self::$cards);
    }

    public function render()
    {
        return view('laravel-crm::livewire.kanban-board.board', parent::render()->getData());
    }
}

class HostDealBoard extends HostBoard
{
    public $model = 'deal';
}

class HostQuoteBoard extends HostBoard
{
    public $model = 'quote';
}

class HostFilteredLeadBoard extends LeadBoard
{
    protected function boardScope($query): void
    {
        parent::boardScope($query);

        $query->where($this->column('title'), '!=', 'Hidden');
    }
}

function hostCard(int $id = 1): array
{
    return [
        'id' => $id,
        'title' => 'Host card',
        'labels' => collect(),
        'stage' => 1,
        'number' => 'H1',
        'amount' => 100,
        'currency' => 'USD',
    ];
}

it('renders a host board with the header count from its cards', function () {
    $this->actingAsUserWithPermissions(['view crm leads']);

    HostBoard::$cards = [hostCard()];

    Livewire::test(HostBoard::class)
        ->assertOk()
        ->assertSee('Host card')
        ->assertSeeHtml('<span>Open (<span data-board-count>1</span>)</span>')
        ->assertSeeHtml('<span>Closed (<span data-board-count>0</span>)</span>');
});

it('offers a host board no load more', function () {
    $this->actingAsUserWithPermissions(['view crm leads']);

    HostBoard::$cards = [hostCard()];

    expect(Livewire::test(HostBoard::class)->html())->toMatch('/<button[^>]*hidden[^>]*data-board-more/');
});

it('finds the deal behind a host board card', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'edit crm deals']);

    $closed = Deal::create(['title' => 'Closed', 'closed_at' => now()]);

    HostBoard::$cards = [hostCard($closed->id)];

    Livewire::test(HostDealBoard::class)->assertOk()->assertSeeHtml("reopen({$closed->id})");
});

it('finds the quote behind a host board card', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);

    $quote = Quote::create(['title' => 'Open', 'currency' => 'USD']);

    HostBoard::$cards = [hostCard($quote->id)];

    Livewire::test(HostQuoteBoard::class)->assertOk();
});

it('gives only a host quote board the shared send drawer', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'view crm leads']);

    HostBoard::$cards = [];

    Livewire::test(HostQuoteBoard::class)->assertSeeLivewire('crm-quote-send');
    Livewire::test(HostBoard::class)->assertDontSeeLivewire('crm-quote-send');
});

// What docs/upgrading.md tells a host with a records() override to do instead.
it('lets a host narrow a paged board through boardScope', function () {
    $this->actingAsUserWithPermissions(['view crm leads']);

    $pipeline = Pipeline::create(['external_id' => (string) Str::uuid(), 'name' => 'Leads', 'model' => Lead::class]);
    $stage = $pipeline->pipelineStages()->create(['external_id' => (string) Str::uuid(), 'name' => 'First', 'order' => 0]);

    $shown = Lead::create(['title' => 'Shown', 'pipeline_stage_id' => $stage->id]);
    Lead::create(['title' => 'Hidden', 'pipeline_stage_id' => $stage->id]);

    $column = collect(Livewire::test(HostFilteredLeadBoard::class)->viewData('stages'))->firstWhere('id', $stage->id);

    expect($column['records']->pluck('id')->all())->toBe([$shown->id])
        ->and($column['count'])->toBe(1);

    // The search joins tables that share the column, and both leads match it.
    $searched = Livewire::test(HostFilteredLeadBoard::class)->set('search', 'n');

    expect(collect($searched->viewData('stages'))->firstWhere('id', $stage->id)['records']->pluck('id')->all())
        ->toBe([$shown->id]);
});
