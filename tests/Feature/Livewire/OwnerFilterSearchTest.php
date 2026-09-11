<?php

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
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
use VentureDrake\LaravelCrm\Livewire\Traits\HasUserOwnerFilter;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\Task;

/*
 * The Owner filter used to hand `User::orderBy('name')->get()` straight to
 * <x-mary-choices>, which json_encodes every option into an Alpine x-data block
 * and renders a <div> per option — and Livewire then serialises and checksums
 * that payload on every round trip. On an install with ~11,500 users a 25-row
 * people page weighed 31.7 MB and spent six seconds in render.
 *
 * HasUserOwnerFilter caps the options at 20 and searches them server-side. The
 * subtle part, and the one most likely to regress, is that MaryUI resolves a
 * selected chip's label out of the same options collection: an owner that falls
 * out of the matches renders as a blank badge unless it is merged back in.
 */

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

/**
 * Seed `$count` users named "Owner 00".."Owner NN" plus a distinctly-named one,
 * and return the distinct user.
 */
function seedOwners(int $count = 40): User
{
    for ($i = 0; $i < $count; $i++) {
        User::create([
            'name' => sprintf('Owner %02d', $i),
            'email' => "owner{$i}@example.com",
            'password' => bcrypt('secret'),
        ]);
    }

    return User::create([
        'name' => 'Andrew Drake',
        'email' => 'andrew@example.com',
        'password' => bcrypt('secret'),
    ]);
}

/**
 * The kanban boards read their columns from a Pipeline; without one `stages()`
 * falls off the end of its `if` and trips its own `Collection` return type.
 */
function seedBoardPipelines(): void
{
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);

    foreach ([Lead::class, Deal::class, Quote::class] as $model) {
        $pipeline = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ]);

        $pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Pending',
            'order' => 0,
        ]);
    }
}

/** Every component that renders an Owner (or Assigned to) filter. */
function ownerFilterComponents(): array
{
    return [
        DealBoard::class, DealIndex::class, DeliveryIndex::class, InvoiceIndex::class,
        LeadBoard::class, LeadIndex::class, OrderIndex::class, OrganizationIndex::class,
        PersonIndex::class, ProductIndex::class, PurchaseOrderIndex::class,
        QuoteBoard::class, QuoteIndex::class, TaskIndex::class, TeamIndex::class,
    ];
}

dataset('owner filter components', [
    'people' => [PersonIndex::class],
    'leads' => [LeadIndex::class],
    'tasks' => [TaskIndex::class],
]);

it('keeps a selected owner in the options when the search no longer matches them', function (string $component) {
    $andrew = seedOwners();

    $users = Livewire::test($component)
        ->set('user_id', [$andrew->id])
        ->call('searchUsers', 'zzzz')
        ->instance()
        ->users();

    // Without the merge this collection is empty and the chip renders blank.
    expect($users->pluck('name')->all())->toBe(['Andrew Drake'])
        ->and($users->first()->id)->toBe($andrew->id);
})->with('owner filter components');

it('caps the options at twenty when nothing has been searched', function (string $component) {
    seedOwners();

    $users = Livewire::test($component)->instance()->users();

    // 'Andrew Drake' and the signed-in 'Test User' sort around 'Owner NN'.
    expect($users)->toHaveCount(20)
        ->and($users->pluck('name')->all())->toBe($users->pluck('name')->sort()->values()->all())
        ->and($users->pluck('name')->all())->toContain('Andrew Drake');
})->with('owner filter components');

it('narrows the options to the search term', function (string $component) {
    $andrew = seedOwners();

    $users = Livewire::test($component)
        ->call('searchUsers', 'Andrew')
        ->instance()
        ->users();

    expect($users->pluck('id')->all())->toBe([$andrew->id]);
})->with('owner filter components');

it('does not render the whole users table into the page', function () {
    seedOwners(40);

    // 'Owner 39' sorts past the twenty-option cap, so its presence in the
    // payload would mean the full collection is being serialised again.
    Livewire::test(PersonIndex::class)
        ->assertOk()
        ->assertDontSee('Owner 39')
        ->assertSee('Owner 00');
});

it('still filters rows by the selected owner', function () {
    $andrew = seedOwners();
    $other = User::where('name', 'Owner 00')->first();

    Person::create(['first_name' => 'Mine', 'last_name' => 'Person', 'user_owner_id' => $andrew->id]);
    Person::create(['first_name' => 'Theirs', 'last_name' => 'Person', 'user_owner_id' => $other->id]);

    Lead::create(['title' => 'Mine', 'user_owner_id' => $andrew->id]);
    Lead::create(['title' => 'Theirs', 'user_owner_id' => $other->id]);

    Task::create(['name' => 'Mine', 'user_assigned_id' => $andrew->id]);
    Task::create(['name' => 'Theirs', 'user_assigned_id' => $other->id]);

    $people = Livewire::test(PersonIndex::class)->set('user_id', [$andrew->id])->instance()->people();
    $leads = Livewire::test(LeadIndex::class)->set('user_id', [$andrew->id])->instance()->leads();
    $tasks = Livewire::test(TaskIndex::class)->set('user_id', [$andrew->id])->instance()->tasks();

    expect($people->pluck('first_name')->all())->toBe(['Mine'])
        ->and($leads->pluck('title')->all())->toBe(['Mine'])
        ->and($tasks->pluck('name')->all())->toBe(['Mine']);
});

it('keeps the owner filter applied on the second page', function () {
    $andrew = seedOwners();
    $other = User::where('name', 'Owner 00')->first();

    for ($i = 0; $i < 30; $i++) {
        Person::create(['first_name' => 'Mine', 'last_name' => (string) $i, 'user_owner_id' => $andrew->id]);
        Person::create(['first_name' => 'Theirs', 'last_name' => (string) $i, 'user_owner_id' => $other->id]);
    }

    $page = Livewire::test(PersonIndex::class)
        ->set('user_id', [$andrew->id])
        ->set('paginators.page', 2)
        ->instance()
        ->people();

    expect($page->total())->toBe(30)
        ->and($page->pluck('first_name')->unique()->all())->toBe(['Mine']);
});

it('renders the searchable owner drawer on every screen that has one', function (string $component) {
    seedOwners(5);
    seedBoardPipelines();

    // `allow-all` and `searchable` are mutually exclusive — MaryUI throws from
    // the Choices constructor if both are passed, so a leftover `allow-all`
    // takes the whole screen down rather than degrading quietly.
    Livewire::test($component)->assertOk();
})->with(ownerFilterComponents());

it('sources every owner filter from the shared trait', function () {
    $missing = array_values(array_filter(
        ownerFilterComponents(),
        fn (string $component) => ! in_array(HasUserOwnerFilter::class, class_uses_recursive($component), true)
    ));

    expect($missing)->toBe([]);
});
