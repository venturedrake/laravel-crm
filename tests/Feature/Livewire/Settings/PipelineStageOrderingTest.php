<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Settings\Pipelines\PipelineShow;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm pipelines', 'edit crm pipelines']);
    $this->pipeline = Pipeline::create(['name' => 'Sales', 'model' => Deal::class]);
    $this->stages = collect(['Closed Won', 'Closed Lost', 'Negotiation'])->map(fn ($name, $index) => $this->pipeline->pipelineStages()->create(['name' => $name, 'order' => $index + 1])
    );
    $this->ids = $this->stages->pluck('id')->all();
    $this->proposed = [$this->ids[2], $this->ids[0], $this->ids[1]];
});

test('moves Negotiation before closed stages and persists after a reload', function () {
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])
        ->assertSeeInOrder(['Closed Won', 'Closed Lost', 'Negotiation'])
        ->assertSee('x-bind:disabled="busy || true"', false)
        ->assertDontSee('@js($loop', false)
        ->call('reorderStages', $this->proposed)
        ->assertHasNoErrors()
        ->assertSeeInOrder(['Negotiation', 'Closed Won', 'Closed Lost']);

    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])
        ->assertSeeInOrder(['Negotiation', 'Closed Won', 'Closed Lost']);
});

test('two open pages cannot silently overwrite one another', function () {
    $first = Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline]);
    $second = Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline]);
    $snapshot = $second->get('orderingSnapshot');
    $first->call('reorderStages', $this->proposed)->assertHasNoErrors();
    $second->call('reorderStages', array_reverse($this->ids))->assertHasNoErrors()
        ->assertSeeInOrder(['Negotiation', 'Closed Won', 'Closed Lost']);
    expect(collect($second->effects['xjs'])->pluck('expression')->implode(' '))->toContain(ucfirst(__('laravel-crm::lang.pipeline_stage_order_changed')));
    expect($second->get('orderingSnapshot'))->not->toBe($snapshot);
    expect($this->pipeline->pipelineStages()->orderBy('order')->pluck('id')->all())->toBe($this->proposed);
    $second->call('reorderStages', $this->ids)->assertHasNoErrors();
    expect($this->pipeline->pipelineStages()->orderBy('order')->pluck('id')->all())->toBe($this->ids);
});

test('the displayed snapshot cannot be replaced by the client', function () {
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])->set('orderingSnapshot', 'forged');
})->throws(CannotUpdateLockedPropertyException::class);

test('view only users see the list without controls and direct writes are forbidden', function () {
    $this->actingAsUserWithPermissions(['view crm pipelines']);
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])
        ->assertSee('Negotiation')->assertDontSee('<span data-stage-handle', false)
        ->assertDontSee('Move up')
        ->call('reorderStages', $this->proposed)->assertForbidden();
    expect($this->stages->map(fn ($stage) => $stage->fresh()->order)->all())->toBe([1, 2, 3]);
});

test('each request rechecks permissions and current team', function ($change) {
    config(['laravel-crm.teams' => true]);
    $user = $this->actingAsUserWithPermissions(['view crm pipelines', 'edit crm pipelines'], ['current_team_id' => 1]);
    $this->pipeline->update(['team_id' => 1]);
    $this->stages->each->update(['team_id' => 1]);
    $page = Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline]);
    if ($change === 'team') {
        $user->update(['current_team_id' => 2]);
        expect(fn () => $page->call('reorderStages', $this->proposed))->toThrow(ModelNotFoundException::class);
    } else {
        $user->update(['crm_permissions' => json_encode(['view crm pipelines'])]);
        $page->call('reorderStages', $this->proposed)->assertForbidden();
    }
})->with(['team', 'permission']);

test('no view permission denies a direct component mount', function () {
    $this->actingAsUserWithPermissions(['edit crm pipelines']);
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])->assertForbidden();
});

test('a stage specific denial hides controls and prevents a direct action', function () {
    $denied = $this->stages[1];
    Gate::before(fn ($user, $ability, $args) => $ability === 'update' && ($args[0] ?? null) instanceof PipelineStage && $args[0]->is($denied) ? false : null);
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])
        ->assertDontSee('Move up')->call('reorderStages', $this->proposed)->assertForbidden();
});

test('empty and single stage lists render without movement controls', function ($count) {
    $this->stages->slice($count)->each->delete();
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])
        ->assertOk()->assertDontSee('Move up')->assertDontSee('<span data-stage-handle', false);
})->with([0, 1]);

test('all board types load the saved stage column order', function ($model, $board) {
    $this->actingAsUser();
    Setting::create(['name' => 'currency', 'value' => 'USD']);
    $this->pipeline->update(['model' => $model]);
    Livewire::test(PipelineShow::class, ['pipeline' => $this->pipeline])->call('reorderStages', $this->proposed)->assertHasNoErrors();
    $component = Livewire::test($board)->assertOk();
    expect($component->instance()->stages()->pluck('id')->all())->toBe($this->proposed);
})->with([
    [Lead::class, LeadBoard::class], [Deal::class, DealBoard::class], [Quote::class, QuoteBoard::class],
]);
