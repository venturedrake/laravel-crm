<?php

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

/*
 * The board scrolls inside an overflow-auto wrapper, which clipped a card's menu at the
 * bottom of a short board, hiding the last items. The menu now lives on <body> while it
 * is open. What that does in a browser can't be seen from here, so these pin the markup
 * it depends on.
 */

dataset('board card menus', [
    'lead' => [LeadBoard::class, Lead::class, 'crm leads'],
    'deal' => [DealBoard::class, Deal::class, 'crm deals'],
    'quote' => [QuoteBoard::class, Quote::class, 'crm quotes'],
]);

function cardMenuBoardHtml(string $component, string $model, string $permission): string
{
    test()->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}", "delete {$permission}"]);

    $pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Pipeline',
        'model' => $model,
    ]);

    $stage = $pipeline->pipelineStages()->create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'First',
        'order' => 0,
    ]);

    $model::create(['title' => 'Card', 'pipeline_stage_id' => $stage->id]);

    return Livewire::test($component)->html();
}

function cardMenuXPath(string $html): DOMXPath
{
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

/**
 * The menu of the board's only card: rendered only while open, on <body>, anchored.
 */
function cardMenuList(string $html): DOMElement
{
    $menus = cardMenuXPath($html)->query(
        '//*[@data-record-id]//*[@data-board-menu][@x-data]'
        ."/template[@x-if = 'open']/template[@x-teleport = 'body']/ul[@data-board-menu-items]"
        ."[@*[name() = 'x-anchor.bottom-end'][. = '\$refs.trigger']]"
    );

    expect($menus->length)->toBe(1);

    return $menus->item(0);
}

it('teleports a card menu to the body and anchors it to its trigger', function (string $component, string $model, string $permission) {
    $html = cardMenuBoardHtml($component, $model, $permission);

    cardMenuList($html);

    // MaryUI's dropdown stays inside the card, which is what got clipped.
    expect(cardMenuXPath($html)->query('//*[@data-record-id]//details')->length)->toBe(0);
})->with('board card menus');

it('keeps the menu items inside the teleported list', function (string $component, string $model, string $permission) {
    $list = cardMenuList(cardMenuBoardHtml($component, $model, $permission));

    expect($list->textContent)->toContain(ucfirst(__('laravel-crm::lang.view')))
        ->toContain(ucfirst(__('laravel-crm::lang.edit')))
        ->toContain(ucfirst(__('laravel-crm::lang.delete')));
})->with('board card menus');

it('closes on an outside click, escape, scroll, resize and drag', function (string $component, string $model, string $permission) {
    $list = cardMenuList(cardMenuBoardHtml($component, $model, $permission));

    $attributes = collect(iterator_to_array($list->attributes))
        ->map(fn (DOMAttr $attribute) => $attribute->value)
        ->keys()
        ->all();

    expect($attributes)->toContain(
        'x-on:click',
        'x-on:click.outside',
        'x-on:keydown.escape.window',
        'x-on:scroll.window.capture',
        'x-on:resize.window',
        'x-on:dragstart.window',
    );

    // A click on the trigger is outside the list, and it toggles the menu itself.
    expect($list->getAttribute('x-on:click.outside'))->toContain('$refs.trigger')
        // The search box scrolls its text back as it blurs, which is any click on the
        // trigger after a long search. Closing on that would shut the menu as it opens.
        ->and($list->getAttribute('x-on:scroll.window.capture'))->toBe('if ($event.target.contains($refs.trigger)) open = false');
})->with('board card menus');

it('takes the teleport template out of the page before Back or Forward saves it', function (string $component, string $model, string $permission) {
    $list = cardMenuList(cardMenuBoardHtml($component, $model, $permission));

    // Only removing the template helps: setting open alone reaches x-if after the page
    // is saved, and the restored list would be one nothing can close.
    expect($list->parentNode->getAttribute('x-ref'))->toBe('teleport')
        ->and($list->getAttribute('x-on:livewire:navigating.window'))->toBe('$refs.teleport.remove(); open = false');
})->with('board card menus');

it('gives a card from load more the same menu', function (string $component, string $model, string $permission) {
    config()->set('laravel-crm.board.per_column', 1);

    cardMenuBoardHtml($component, $model, $permission);

    $stage = Pipeline::where('model', $model)->first()->pipelineStages()->first();
    $model::create(['title' => 'Second', 'pipeline_stage_id' => $stage->id]);

    $board = Livewire::test($component)->call('loadMore', $stage->id);

    $page = collect($board->effects['dispatches'])->firstWhere('name', 'board-column-loaded')['params']['html'];

    cardMenuList($page);
})->with('board card menus');

it('teleports the feature board card menu too', function () {
    $this->actingAsUserWithPermissions(['view crm features', 'edit crm features', 'delete crm features']);

    $status = FeatureStatus::create(['name' => 'Open', 'order' => 0]);
    Feature::create(['title' => 'Card', 'feature_status_id' => $status->id]);

    cardMenuList(Livewire::test(FeatureBoard::class)->html());
});

it('stops sortable treating a press on the menu as a drag, without eating the click', function () {
    $sortable = file_get_contents(dirname(__DIR__, 3).'/resources/views/livewire/kanban-board/sortable.blade.php');

    expect($sortable)->toContain("filter: '[data-board-menu]'")
        ->toContain('preventOnFilter: false');
});
