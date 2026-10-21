<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

/**
 * Seeded-role smoke matrix for the livewire-authz-checks series (US-001..US-009).
 *
 * The series added ~189 authorize() guards, 21 route-level can: middlewares and 38
 * Blade gates. The single largest risk it carries is a *lockout*: a seeded role that
 * could do something before the change being denied afterwards. This file is the
 * automated half of that check.
 *
 * Two things are DERIVED FROM SOURCE rather than hardcoded, so the matrix cannot
 * silently drift away from what actually ships:
 *
 *   1. Each seeded role's permission set is parsed out of LaravelCrmTablesSeeder --
 *      Owner and Admin receive Permission::all(), Manager and Employee receive
 *      explicit givePermissionTo([...]) lists.
 *   2. The permission string each policy method checks is parsed out of the policy
 *      itself. This matters because the mapping is not always the obvious one --
 *      PipelineStagePolicy checks 'crm pipelines', FieldGroupPolicy checks
 *      'crm fields' -- and a hardcoded guess would report a false regression.
 *
 * The matrix then asserts BOTH directions for every (role, model, ability) triple:
 * a role holding the permission must be allowed (the lockout regression), and a role
 * without it must be denied (proving the guard is not over-broad). Both parsers have
 * their own vacuity guard so a broken parse fails loudly instead of asserting nothing.
 */

/**
 * Render-stub subclass. Livewire renders on mount and the deals index blade reaches
 * for activity/contact tables the minimal TestSchema does not ship. Overriding only
 * render() leaves the real action methods -- and the authorize() guards inside them --
 * intact, so the production authorization path still runs against the real policies.
 */
class SmokeDealIndex extends DealIndex
{
    public function render()
    {
        return '<div></div>';
    }
}

function smokeSeederSource(): string
{
    $path = __DIR__.'/../../database/seeders/LaravelCrmTablesSeeder.php';

    expect(file_exists($path))->toBeTrue("Seeder not found at {$path}");

    return file_get_contents($path);
}

/**
 * Every permission the seeder creates. This is exactly what Owner and Admin receive,
 * since both are granted Permission::all().
 */
function smokeAllSeededPermissions(): array
{
    preg_match_all(
        "/Permission::firstOrCreate\(\['name' => '([^']+)'/",
        smokeSeederSource(),
        $m
    );

    return array_values(array_unique($m[1]));
}

/**
 * The explicit permission list a named role is granted via givePermissionTo([...]).
 */
function smokeRolePermissionList(string $role): array
{
    $src = smokeSeederSource();

    // Anchor on the role's own firstOrCreate block, then take the givePermissionTo
    // array that immediately follows it.
    $pattern = "/'name' => '".preg_quote($role, '/')."'.*?givePermissionTo\(\[(.*?)\]\);/s";

    expect(preg_match($pattern, $src, $m))->toBe(1, "Could not locate the {$role} givePermissionTo block in the seeder");

    preg_match_all("/'([^']+)'/", $m[1], $perms);

    return array_values(array_unique($perms[1]));
}

/**
 * Permission set per seeded role, exactly as the seeder grants them.
 */
function smokeSeededRolePermissions(): array
{
    $all = smokeAllSeededPermissions();

    return [
        'Owner' => $all,
        'Admin' => $all,
        'Manager' => smokeRolePermissionList('Manager'),
        'Employee' => smokeRolePermissionList('Employee'),
    ];
}

/**
 * The permission string a given policy method checks, parsed from the policy source.
 * Returns null when the method does not gate on a permission (e.g. SettingPolicy's
 * create() and delete() return false unconditionally) so callers can skip it.
 */
function smokePolicyPermission(string $policyShortName, string $ability): ?string
{
    $path = __DIR__."/../../src/Policies/{$policyShortName}Policy.php";

    if (! file_exists($path)) {
        return null;
    }

    $src = file_get_contents($path);

    if (! preg_match('/\n    public function '.preg_quote($ability, '/').'\s*\(.*?\n    \}/s', $src, $method)) {
        return null;
    }

    if (! preg_match("/hasPermissionTo\('([^']+)'\)/", $method[0], $perm)) {
        return null;
    }

    return $perm[1];
}

/**
 * The core entities the AC names, with the abilities the series guards on each.
 * Abilities taking no model instance (viewAny/create/manageProducts) are passed to
 * the Gate as a class-string, exactly as the route middleware and components do.
 */
function smokeAbilityMatrix(): array
{
    return [
        'Deal' => ['model' => Deal::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete', 'manageProducts']],
        'Lead' => ['model' => Lead::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete']],
        'Quote' => ['model' => Quote::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete', 'manageProducts']],
        'Order' => ['model' => Order::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete', 'manageProducts']],
        'Invoice' => ['model' => Invoice::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete']],
        'Person' => ['model' => Person::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete']],
        'Organization' => ['model' => Organization::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete']],
        'Task' => ['model' => Task::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete']],
        'Setting' => ['model' => Setting::class, 'abilities' => ['viewAny', 'view', 'update']],
        'User' => ['model' => User::class, 'abilities' => ['viewAny', 'view', 'create', 'update', 'delete']],
    ];
}

/**
 * Whether the control at $position sits inside a still-open @can($gate) frame.
 *
 * Walks the Blade @can/@canany directives before $position with a stack, so an
 * earlier already-closed gate for the same permission cannot produce a false pass.
 * A plain "the gate string appears somewhere above" check is vacuous here -- the
 * deals index opens @can('edit crm deals') three separate times.
 */
function smokeGateIsOpenAt(string $blade, int $position, string $gate): bool
{
    $before = substr($blade, 0, $position);

    preg_match_all('/@(endcanany|endcan|canany|can)\b(\s*\(([^)]*)\))?/', $before, $matches, PREG_SET_ORDER);

    $stack = [];

    foreach ($matches as $match) {
        match ($match[1]) {
            'can', 'canany' => $stack[] = trim($match[3] ?? ''),
            'endcan', 'endcanany' => array_pop($stack),
        };
    }

    return in_array($gate, $stack, true);
}

/**
 * An instance of the given model, for the abilities whose policy signature takes one.
 */
function smokeInstanceFor(string $entity)
{
    return match ($entity) {
        'Deal' => Deal::create(['title' => 'Smoke deal']),
        'Lead' => Lead::create(['title' => 'Smoke lead']),
        'Quote' => Quote::create(['title' => 'Smoke quote']),
        'Order' => Order::create([]),
        'Invoice' => Invoice::create([]),
        'Person' => Person::create(['first_name' => 'Smoke', 'last_name' => 'Person']),
        'Organization' => Organization::create(['name' => 'Smoke Org']),
        'Task' => Task::create(['name' => 'Smoke task']),
        'Setting' => Setting::create(['name' => 'smoke_setting', 'value' => '1']),
        'User' => User::create(['name' => 'Smoke Subject', 'email' => 'smoke-subject'.uniqid().'@example.com', 'password' => bcrypt('secret')]),
    };
}

// ---------------------------------------------------------------------------
// Vacuity guards -- both parsers must actually find something.
// ---------------------------------------------------------------------------

it('derives a non-empty permission set for every seeded role from the seeder', function () {
    $roles = smokeSeededRolePermissions();

    expect(array_keys($roles))->toBe(['Owner', 'Admin', 'Manager', 'Employee']);

    foreach ($roles as $role => $permissions) {
        expect(count($permissions))->toBeGreaterThan(50, "Parsed suspiciously few permissions for {$role}");
    }

    // Owner/Admin hold strictly more than Manager, which holds strictly more than
    // Employee -- if the parse silently returned the same list for all four this fails.
    expect(count($roles['Owner']))->toBeGreaterThan(count($roles['Manager']))
        ->and(count($roles['Manager']))->toBeGreaterThan(count($roles['Employee']));
});

it('derives the permission string that each policy method checks', function () {
    $checked = 0;

    foreach (smokeAbilityMatrix() as $entity => $spec) {
        foreach ($spec['abilities'] as $ability) {
            $permission = smokePolicyPermission($entity, $ability);

            if ($permission === null) {
                continue;
            }

            expect(preg_match('/^(view|create|edit|delete) crm /', $permission))
                ->toBe(1, "Unexpected permission shape '{$permission}' on {$entity}::{$ability}");

            $checked++;
        }
    }

    // 10 entities x ~5 abilities, minus the handful that gate on nothing.
    expect($checked)->toBeGreaterThan(40, 'Policy permission parser matched suspiciously few methods');
});

// ---------------------------------------------------------------------------
// AC 1 -- a view-only user on the deals index.
// ---------------------------------------------------------------------------

it('forbids a view-only user from deleting a deal and leaves the deal present', function () {
    $this->actingAsUserWithPermissions(['view crm deals']);
    $deal = Deal::create(['title' => 'Smoke deal']);

    Livewire::test(SmokeDealIndex::class)
        ->call('delete', $deal->id)
        ->assertForbidden();

    expect(Deal::find($deal->id))->not->toBeNull();
});

it('forbids a view-only user from marking a deal won or lost and leaves it open', function () {
    $this->actingAsUserWithPermissions(['view crm deals']);
    $deal = Deal::create(['title' => 'Smoke deal']);

    Livewire::test(SmokeDealIndex::class)->call('won', $deal->id)->assertForbidden();
    Livewire::test(SmokeDealIndex::class)->call('lost', $deal->id)->assertForbidden();

    expect(Deal::find($deal->id))->not->toBeNull()
        ->and(Deal::find($deal->id)->closed_status)->toBeNull();
});

it('hides the delete, won, lost and reopen buttons from a view-only user on the deals index', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/livewire/deals/deal-index.blade.php');

    // The won/lost/reopen trio sits inside an edit gate.
    foreach (['won', 'lost', 'reopen'] as $action) {
        $position = strpos($blade, 'wire:click="'.$action.'(');

        expect($position)->toBeInt("No {$action} button found on the deals index");
        expect(smokeGateIsOpenAt($blade, $position, "'edit crm deals'"))
            ->toBeTrue("The {$action} button is not inside an @can('edit crm deals') gate");
    }

    // Delete is a modal, not a direct wire:click -- the trigger opens the dialog and
    // the confirm component holds the wire:click="delete". Both must be gated, or a
    // view-only user gets a dialog in the DOM they can still fire.
    foreach (['onclick="modalDeleteDeal', '<x-crm-delete-confirm'] as $marker) {
        $position = strpos($blade, $marker);

        expect($position)->toBeInt("No '{$marker}' found on the deals index");
        expect(smokeGateIsOpenAt($blade, $position, "'delete crm deals'"))
            ->toBeTrue("'{$marker}' is not inside an @can('delete crm deals') gate");
    }
});

// ---------------------------------------------------------------------------
// AC 2 -- the seeded-role regression matrix.
// ---------------------------------------------------------------------------

it('preserves every ability each seeded role held before the authorization backfill', function (string $role) {
    $permissions = smokeSeededRolePermissions()[$role];
    $this->actingAsUserWithPermissions($permissions);

    $granted = [];
    $denied = [];
    $checked = 0;

    foreach (smokeAbilityMatrix() as $entity => $spec) {
        $instance = null;

        foreach ($spec['abilities'] as $ability) {
            $permission = smokePolicyPermission($entity, $ability);

            if ($permission === null) {
                continue;
            }

            // viewAny/create/manageProducts take no model; the rest take an instance.
            $needsInstance = ! in_array($ability, ['viewAny', 'create', 'manageProducts'], true);

            if ($needsInstance && $instance === null) {
                $instance = smokeInstanceFor($entity);
            }

            $subject = $needsInstance ? $instance : $spec['model'];
            $allowed = Gate::allows($ability, $subject);
            $shouldAllow = in_array($permission, $permissions, true);
            $label = "{$entity}::{$ability} (needs '{$permission}')";

            if ($shouldAllow && ! $allowed) {
                $denied[] = $label;
            }

            if (! $shouldAllow && $allowed) {
                $granted[] = $label;
            }

            $checked++;
        }
    }

    // A pass with a zero denominator is a failure.
    expect($checked)->toBeGreaterThan(40, "Matrix examined only {$checked} abilities for {$role}");

    expect($denied)->toBe([], "{$role} LOST access it held before the change: ".implode(', ', $denied));
    expect($granted)->toBe([], "{$role} GAINED access it should not have: ".implode(', ', $granted));
})->with(['Owner', 'Admin', 'Manager', 'Employee']);

it('denies a fully-permissioned Owner every ability on a module that is switched off', function () {
    // US-009's upgrade note records this as the one documented way a seeded role can
    // still lose access: 14 policies AND an isEnabled() module gate onto the permission
    // check, while the Blade @can only tests the permission string. On a host with a
    // trimmed config('laravel-crm.modules') the button renders and the action 403s.
    //
    // This is the failure mode the matrix above is built to catch, so it needs its own
    // coverage -- the modules array cannot be varied by editing config/laravel-crm.php,
    // because testbench serves a published copy from its own app skeleton that shadows
    // the package file. It has to be set at runtime.
    $this->actingAsUserWithPermissions(smokeSeededRolePermissions()['Owner']);

    $deal = Deal::create(['title' => 'Module-gated deal']);
    $task = Task::create(['name' => 'Ungated task']);

    expect(Gate::allows('view', $deal))->toBeTrue('Precondition: deals module is on');

    config(['laravel-crm.modules' => array_values(array_diff(
        config('laravel-crm.modules'),
        ['deals']
    ))]);

    // Module-gated policy: every ability denies, despite Owner holding the permission.
    expect(Gate::allows('viewAny', Deal::class))->toBeFalse()
        ->and(Gate::allows('view', $deal))->toBeFalse()
        ->and(Gate::allows('create', Deal::class))->toBeFalse()
        ->and(Gate::allows('update', $deal))->toBeFalse()
        ->and(Gate::allows('delete', $deal))->toBeFalse()
        ->and(Gate::allows('manageProducts', Deal::class))->toBeFalse();

    // Entities with no module gate are unaffected, which is what makes this a
    // module-configuration issue rather than an authorization regression.
    expect(Gate::allows('view', $task))->toBeTrue()
        ->and(Gate::allows('update', $task))->toBeTrue();
});

// ---------------------------------------------------------------------------
// AC 3 -- product sub-resource routes.
// ---------------------------------------------------------------------------

it('resolves the class-string manageProducts guard on the deal, quote and order product routes', function (string $role) {
    $this->actingAsUserWithPermissions(smokeSeededRolePermissions()[$role]);

    // These are the exact arguments the group-level route middleware constructs:
    // can:manageProducts,VentureDrake\LaravelCrm\Models\{Deal,Quote,Order}
    expect(Gate::allows('manageProducts', Deal::class))->toBeTrue("{$role} cannot manage deal products")
        ->and(Gate::allows('manageProducts', Quote::class))->toBeTrue("{$role} cannot manage quote products")
        ->and(Gate::allows('manageProducts', Order::class))->toBeTrue("{$role} cannot manage order products");
})->with(['Owner', 'Admin', 'Manager', 'Employee']);

it('denies the manageProducts guard when the role cannot edit the parent entity', function () {
    // The guard is deliberately gated on `edit crm deals`, NOT on ProductPolicy --
    // neither Manager nor Employee holds any `crm products` permission, so gating on
    // the product catalog would have locked them out of quote building.
    $this->actingAsUserWithPermissions(['view crm deals', 'edit crm products']);

    expect(Gate::allows('manageProducts', Deal::class))->toBeFalse()
        ->and(Gate::allows('manageProducts', Quote::class))->toBeFalse()
        ->and(Gate::allows('manageProducts', Order::class))->toBeFalse();
});
