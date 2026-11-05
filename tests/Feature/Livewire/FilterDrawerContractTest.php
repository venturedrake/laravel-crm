<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureBoard;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationIndex;
use VentureDrake\LaravelCrm\Livewire\People\PersonIndex;
use VentureDrake\LaravelCrm\Livewire\Products\ProductIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskIndex;
use VentureDrake\LaravelCrm\Livewire\Teams\TeamIndex;
use VentureDrake\LaravelCrm\Livewire\Users\UserIndex;
use VentureDrake\LaravelCrm\Models\Team;

/*
 * Every board and list page repeats the same filter drawer, which binds to its
 * component by name. Blade can't check those names, so when the paged boards lost
 * clear() nothing noticed until someone clicked Reset and got a 500. Livewire only
 * resolves a name when it's used, so this reads each one up front.
 */

const FILTER_DRAWER_RESET = 'wire:click="clear"';

function filterDrawers(): array
{
    return [
        'lead board' => [LeadBoard::class, 'leads/lead-board'],
        'deal board' => [DealBoard::class, 'deals/deal-board'],
        'quote board' => [QuoteBoard::class, 'quotes/quote-board'],
        'lead index' => [LeadIndex::class, 'leads/lead-index'],
        'deal index' => [DealIndex::class, 'deals/deal-index'],
        'quote index' => [QuoteIndex::class, 'quotes/quote-index'],
        'order index' => [OrderIndex::class, 'orders/order-index'],
        'invoice index' => [InvoiceIndex::class, 'invoices/invoice-index'],
        'delivery index' => [DeliveryIndex::class, 'deliveries/delivery-index'],
        'purchase order index' => [PurchaseOrderIndex::class, 'purchase-orders/purchase-order-index'],
        'person index' => [PersonIndex::class, 'people/person-index'],
        'organization index' => [OrganizationIndex::class, 'organizations/organization-index'],
        'product index' => [ProductIndex::class, 'products/product-index'],
        'task index' => [TaskIndex::class, 'tasks/task-index'],
        'team index' => [TeamIndex::class, 'teams/team-index'],
        'user index' => [UserIndex::class, 'users/user-index'],
        'feature index' => [FeatureIndex::class, 'features/feature-index'],
    ];
}

dataset('filter drawers', filterDrawers());

function filterDrawerViewPath(string $view): string
{
    return dirname(__DIR__, 3)."/resources/views/livewire/{$view}.blade.php";
}

/**
 * The drawer as rendered, on its own: a page can also embed a child component, like
 * the Send drawer on quotes, and its bindings are that child's business.
 */
function renderedFilterDrawer(string $html): string
{
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    $drawers = (new DOMXPath($document))
        ->query("//*[contains(@class, 'drawer-side')][.//@*[name() = 'wire:click' and . = 'clear']]");

    return $drawers->length ? $document->saveHTML($drawers->item(0)) : '';
}

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);

    if (! Schema::hasTable('roles')) {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('crm_role')->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('model_has_roles')) {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }
});

it('has a method for every wire:click and a property for every binding', function (string $component, string $view) {
    $drawer = renderedFilterDrawer(Livewire::test($component)->html());

    expect($drawer)->toContain(FILTER_DRAWER_RESET);

    // The source adds what the empty test data doesn't render, like a row's actions.
    $markup = $drawer.file_get_contents(filterDrawerViewPath($view));

    preg_match_all('/wire:click(?:\.[\w.-]+)*="\s*(\w+)/', $markup, $clicks);
    preg_match_all('/search-function="(\w+)"/', $markup, $searches);

    foreach (array_unique([...$clicks[1], ...$searches[1]]) as $method) {
        expect(method_exists($component, $method) && (new ReflectionMethod($component, $method))->isPublic())
            ->toBeTrue("{$component} has no public {$method}()");
    }

    preg_match_all('/wire:model(?:\.[\w.-]+)*="(\w+)/', $markup, $models);
    preg_match_all('/\$wire\.(\w+)\b(?!\s*\()/', $markup, $entangled);

    foreach (array_unique([...$models[1], ...$entangled[1]]) as $property) {
        expect(property_exists($component, $property))
            ->toBeTrue("{$component} has no \${$property}");
    }
})->with('filter drawers');

it('resets the filters and closes the drawer', function (string $component) {
    Livewire::test($component)
        ->set('search', 'Anything')
        ->set('showFilters', true)
        ->call('clear')
        ->assertOk()
        ->assertSet('search', '')
        ->assertSet('showFilters', false);
})->with('filter drawers');

it('covers every view with a filter drawer', function () {
    $views = collect(glob(dirname(__DIR__, 3).'/resources/views/livewire/*/*.blade.php'))
        ->filter(fn (string $path) => str_contains(file_get_contents($path), FILTER_DRAWER_RESET))
        ->map(fn (string $path) => str($path)->after('resources/views/livewire/')->before('.blade.php')->toString())
        ->sort()
        ->values();

    expect($views->all())->toBe(collect(filterDrawers())->pluck(1)->sort()->values()->all());
});

it('filters teams by owner from the drawer', function () {
    $owner = auth()->user();

    $owned = Team::create(['name' => 'Owned', 'user_id' => $owner->id]);
    $other = Team::create(['name' => 'Other', 'user_id' => $owner->id + 1]);

    $teams = fn ($component) => $component->viewData('teams')->pluck('id')->all();

    $component = Livewire::test(TeamIndex::class)->set('user_id', [$owner->id]);

    expect($teams($component))->toBe([$owned->id])
        ->and($teams($component->call('clear')))->toContain($owned->id, $other->id);
});

it('clears the feature board search and leaves it able to drag', function () {
    Livewire::test(FeatureBoard::class, ['sortable' => true, 'sortableBetweenStages' => false])
        ->set('search', 'Anything')
        ->call('clear')
        ->assertOk()
        ->assertSet('search', '')
        ->assertSet('sortable', true)
        ->assertSet('sortableBetweenStages', false);
});
