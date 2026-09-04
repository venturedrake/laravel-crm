<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/**
 * Render-stub subclasses.
 *
 * The ordering assertions only exercise onStageSorted()/onStageChanged(); stubbing
 * render() keeps the mount cheap while leaving those methods -- and their
 * $this->authorize() guards -- exactly as they ship.
 */
class OrderingLeadBoard extends LeadBoard
{
    public function render()
    {
        return '<div></div>';
    }
}
class OrderingDealBoard extends DealBoard
{
    public function render()
    {
        return '<div></div>';
    }
}
class OrderingQuoteBoard extends QuoteBoard
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);

    foreach ([Lead::class, Deal::class, Quote::class] as $model) {
        $pipeline = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ]);

        foreach (['Pending', 'Draft'] as $order => $stage) {
            $pipeline->pipelineStages()->create([
                'external_id' => Str::uuid()->toString(),
                'name' => $stage,
                'order' => $order,
            ]);
        }
    }
});

/*
 * A stage container holds more than the record cards: each card is followed by its
 * delete-confirm <dialog>, and quotes may add a send component. A host running a
 * published (and therefore stale) copy of kanban-board/sortable.blade.php still
 * harvests those sibling ids, so the boards receive arrays like
 * [1, 'modalDeleteLead1', 2]. Those ids must be skipped rather than fatally
 * dereferenced, and they must not consume an order number either.
 */
$boards = [
    'lead' => [OrderingLeadBoard::class, Lead::class, 'crm leads', 'modalDeleteLead'],
    'deal' => [OrderingDealBoard::class, Deal::class, 'crm deals', 'modalDeleteDeal'],
    'quote' => [OrderingQuoteBoard::class, Quote::class, 'crm quotes', 'modalDeleteQuote'],
];

foreach ($boards as $label => [$component, $model, $permission, $dialogPrefix]) {
    it("orders the {$label} board contiguously when unresolvable ids are interleaved on sort", function () use ($component, $model, $permission, $dialogPrefix) {
        $this->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}"]);

        $first = $model::create(['title' => 'First']);
        $second = $model::create(['title' => 'Second']);

        Livewire::test($component)
            ->call('onStageSorted', [$first->id, $dialogPrefix.$first->id, $second->id, $dialogPrefix.$second->id])
            ->assertOk();

        expect($first->fresh()->pipeline_stage_order)->toBe(1)
            ->and($second->fresh()->pipeline_stage_order)->toBe(2);
    });

    it("orders the {$label} board contiguously when unresolvable ids are interleaved on stage change", function () use ($component, $model, $permission, $dialogPrefix) {
        $this->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}"]);

        $moved = $model::create(['title' => 'Moved']);
        $stayed = $model::create(['title' => 'Stayed']);
        $stage = Pipeline::where('model', $model)->first()->pipelineStages()->orderBy('order')->first();

        Livewire::test($component)
            ->call(
                'onStageChanged',
                $moved->id,
                $stage->id,
                [$stayed->id, $dialogPrefix.$stayed->id],
                [$dialogPrefix.$moved->id, $moved->id],
            )
            ->assertOk();

        expect($moved->fresh()->pipeline_stage_id)->toBe($stage->id)
            ->and($moved->fresh()->pipeline_stage_order)->toBe(1)
            ->and($stayed->fresh()->pipeline_stage_order)->toBe(1);
    });

    it("ignores an empty {$label} id rather than fatally dereferencing it", function () use ($component, $model, $permission) {
        $this->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}"]);

        $record = $model::create(['title' => 'Only']);

        Livewire::test($component)
            ->call('onStageSorted', ['', $record->id])
            ->assertOk();

        expect($record->fresh()->pipeline_stage_order)->toBe(1);
    });

    it("forbids sorting the {$label} board without the edit permission", function () use ($component, $model, $permission) {
        $this->actingAsUserWithPermissions(["view {$permission}"]);

        $first = $model::create(['title' => 'First']);
        $second = $model::create(['title' => 'Second']);

        Livewire::test($component)
            ->call('onStageSorted', [$first->id, $second->id])
            ->assertForbidden();

        expect($first->fresh()->pipeline_stage_order)->toBeNull()
            ->and($second->fresh()->pipeline_stage_order)->toBeNull();
    });

    it("forbids sorting the {$label} board when a record later in the batch is not editable", function () use ($component, $model, $permission) {
        $this->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}"]);

        $editable = $model::create(['title' => 'Editable']);
        $locked = $model::create(['title' => 'Locked']);

        // The shipped policies are permission-based, so deny one record outright to model
        // what per-record authorization has to catch: authorizing only the first record in
        // the batch let every id behind it through.
        Gate::before(function ($user, $ability, $arguments) use ($model, $locked) {
            if ($ability === 'update' && ($arguments[0] ?? null) instanceof $model && $arguments[0]->is($locked)) {
                return false;
            }
        });

        Livewire::test($component)
            ->call('onStageSorted', [$editable->id, $locked->id])
            ->assertForbidden();

        // The denial has to roll back the record ahead of it in the batch too, otherwise the
        // stage is left half renumbered against an order the user was never allowed to apply.
        expect($locked->fresh()->pipeline_stage_order)->toBeNull()
            ->and($editable->fresh()->pipeline_stage_order)->toBeNull();
    });

    it("rolls back the {$label} stage change when a record in the batch is not editable", function () use ($component, $model, $permission) {
        $this->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}"]);

        $moved = $model::create(['title' => 'Moved']);
        $locked = $model::create(['title' => 'Locked']);
        $stage = Pipeline::where('model', $model)->first()->pipelineStages()->orderBy('order')->first();

        Gate::before(function ($user, $ability, $arguments) use ($model, $locked) {
            if ($ability === 'update' && ($arguments[0] ?? null) instanceof $model && $arguments[0]->is($locked)) {
                return false;
            }
        });

        Livewire::test($component)
            ->call('onStageChanged', $moved->id, $stage->id, [$locked->id], [$moved->id])
            ->assertForbidden();

        // The stage move is written before the batch is renumbered, so it has to roll back
        // with it -- the card must not stay in the new stage on a rejected drag.
        expect($moved->fresh()->pipeline_stage_id)->toBeNull()
            ->and($moved->fresh()->pipeline_stage_order)->toBeNull()
            ->and($locked->fresh()->pipeline_stage_order)->toBeNull();
    });
}

it('marks lead board record cards with a data-record-id the sortable script can filter on', function () {
    $this->actingAsUserWithPermissions(['view crm leads', 'edit crm leads']);

    $stage = Pipeline::where('model', Lead::class)->first()->pipelineStages()->orderBy('order')->first();
    $lead = Lead::create(['title' => 'Board lead', 'pipeline_stage_id' => $stage->id]);

    Livewire::test(LeadBoard::class)
        ->assertOk()
        ->assertSee('data-record-id="'.$lead->id.'"', false);
});
