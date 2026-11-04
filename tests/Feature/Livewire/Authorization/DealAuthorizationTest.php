<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Livewire\Deals\DealShow;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Pipeline;

/**
 * Render-stub subclasses.
 *
 * Livewire renders a component on mount, and the CRM index/show/edit blades reach for
 * activity + contact tables the minimal TestSchema does not ship. Overriding only
 * render() leaves the real action methods -- and the $this->authorize() guards inside
 * them -- completely intact, so these tests still exercise the production
 * authorization path against the real policies.
 */
class AuthzDealIndex extends DealIndex
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzDealCreate extends DealCreate
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzDealShow extends DealShow
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzDealBoard extends DealBoard
{
    public function render()
    {
        return '<div></div>';
    }
}

it('forbids deleting a deal without the delete permission and leaves the record intact', function () {
    $this->actingAsUserWithPermissions(['view crm deals']);
    $record = Deal::create(['title' => 'Authz deal']);

    Livewire::test(AuthzDealIndex::class)
        ->call('delete', $record->id)
        ->assertForbidden();

    expect(Deal::find($record->id))->not->toBeNull();
});

it('allows deleting a deal with the delete permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'delete crm deals']);
    $record = Deal::create(['title' => 'Authz deal']);

    Livewire::test(AuthzDealIndex::class)
        ->call('delete', $record->id)
        ->assertOk();

    expect(Deal::find($record->id))->toBeNull();
});

it('forbids creating a deal without the create permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals']);

    Livewire::test(AuthzDealCreate::class)
        ->call('save')
        ->assertForbidden();
});

it('allows a user holding the create permission past the deal create guard', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'create crm deals']);

    // Not forbidden: the guard passes and execution reaches validation, which is what
    // proves a seeded role that can create has not lost access.
    Livewire::test(AuthzDealCreate::class)
        ->call('save')
        ->assertOk();
});

it('forbids marking a deal won without the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals']);
    $record = Deal::create(['title' => 'Authz deal']);

    Livewire::test(AuthzDealShow::class, ['deal' => $record])
        ->call('won', $record->id)
        ->assertForbidden();

    expect($record->fresh()->closed_status)->toBeNull();
});

it('allows marking a deal won with the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'edit crm deals']);
    $record = Deal::create(['title' => 'Authz deal']);

    Livewire::test(AuthzDealShow::class, ['deal' => $record])
        ->call('won', $record->id)
        ->assertOk();

    expect($record->fresh()->closed_status)->toBe('won');
});

it('forbids reordering the deal board without the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals']);
    $record = Deal::create(['title' => 'Authz deal']);

    Livewire::test(AuthzDealBoard::class)
        ->call('onStageChanged', $record->id, 1, [], [])
        ->assertForbidden();

    expect($record->fresh()->pipeline_stage_id)->toBeNull();
});

it('allows reordering the deal board with the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deals', 'edit crm deals']);
    $record = Deal::create(['title' => 'Authz deal']);

    $stage = Pipeline::where('model', Deal::class)->first()->pipelineStages()->first();

    Livewire::test(AuthzDealBoard::class)
        ->call('onStageChanged', $record->id, $stage->id, [], [])
        ->assertOk();

    expect($record->fresh()->pipeline_stage_id)->toBe($stage->id);
});
