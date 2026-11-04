<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteShow;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

/**
 * Render-stub subclasses.
 *
 * Livewire renders a component on mount, and the CRM index/show/edit blades reach for
 * activity + contact tables the minimal TestSchema does not ship. Overriding only
 * render() leaves the real action methods -- and the $this->authorize() guards inside
 * them -- completely intact, so these tests still exercise the production
 * authorization path against the real policies.
 */
class AuthzQuoteIndex extends QuoteIndex
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzQuoteCreate extends QuoteCreate
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzQuoteShow extends QuoteShow
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzQuoteBoard extends QuoteBoard
{
    public function render()
    {
        return '<div></div>';
    }
}

it('forbids deleting a quote without the delete permission and leaves the record intact', function () {
    $this->actingAsUserWithPermissions(['view crm quotes']);
    $record = Quote::create(['title' => 'Authz quote']);

    Livewire::test(AuthzQuoteIndex::class)
        ->call('delete', $record->id)
        ->assertForbidden();

    expect(Quote::find($record->id))->not->toBeNull();
});

it('allows deleting a quote with the delete permission', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'delete crm quotes']);
    $record = Quote::create(['title' => 'Authz quote']);

    Livewire::test(AuthzQuoteIndex::class)
        ->call('delete', $record->id)
        ->assertOk();

    expect(Quote::find($record->id))->toBeNull();
});

it('forbids creating a quote without the create permission', function () {
    $this->actingAsUserWithPermissions(['view crm quotes']);

    Livewire::test(AuthzQuoteCreate::class)
        ->call('save')
        ->assertForbidden();
});

it('allows a user holding the create permission past the quote create guard', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'create crm quotes']);

    // Not forbidden: the guard passes and execution reaches validation, which is what
    // proves a seeded role that can create has not lost access.
    Livewire::test(AuthzQuoteCreate::class)
        ->call('save')
        ->assertOk();
});

it('forbids accepting a quote without the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm quotes']);
    $record = Quote::create(['title' => 'Authz quote']);

    Livewire::test(AuthzQuoteShow::class, ['quote' => $record])
        ->call('accept', $record->id)
        ->assertForbidden();

    expect($record->fresh()->accepted_at)->toBeNull();
});

it('allows accepting a quote with the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);
    $record = Quote::create(['title' => 'Authz quote']);

    Livewire::test(AuthzQuoteShow::class, ['quote' => $record])
        ->call('accept', $record->id)
        ->assertOk();

    expect($record->fresh()->accepted_at)->not->toBeNull();
});

it('forbids reordering the quote board without the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm quotes']);
    $record = Quote::create(['title' => 'Authz quote']);

    Livewire::test(AuthzQuoteBoard::class)
        ->call('onStageChanged', $record->id, 1, [], [])
        ->assertForbidden();

    expect($record->fresh()->pipeline_stage_id)->toBeNull();
});

it('allows reordering the quote board with the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);
    $record = Quote::create(['title' => 'Authz quote']);

    $stage = Pipeline::where('model', Quote::class)->first()->pipelineStages()->first();

    Livewire::test(AuthzQuoteBoard::class)
        ->call('onStageChanged', $record->id, $stage->id, [], [])
        ->assertOk();

    expect($record->fresh()->pipeline_stage_id)->toBe($stage->id);
});
