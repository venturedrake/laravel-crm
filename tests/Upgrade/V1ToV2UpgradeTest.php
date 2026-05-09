<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use VentureDrake\LaravelCrm\Models\Permission;

/**
 * End-to-end test of the v1 → v2 upgrade pipeline driven by `laravelcrm:v2`.
 *
 * @group upgrade
 */
const V1_ORG_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Organisation';
const V1_CLIENT_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Client';
const V1_PERSON_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Person';
const V2_ORG_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Organization';
const V2_CUSTOMER_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Customer';

function seedV1Data(): void
{
    $prefix = config('laravel-crm.db_table_prefix');
    $now = now();

    DB::table($prefix.'organisation_types')->insert([
        ['id' => 1, 'name' => 'Customer', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'Supplier', 'created_at' => $now, 'updated_at' => $now],
    ]);
    DB::table($prefix.'organisations')->insert([
        ['id' => 1, 'external_id' => 'org-1', 'name' => 'Acme Inc', 'organisation_type_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'external_id' => 'org-2', 'name' => 'Globex Ltd', 'organisation_type_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);
    DB::table($prefix.'people')->insert([
        ['id' => 1, 'external_id' => 'p-1', 'first_name' => 'Alice', 'last_name' => 'Doe', 'organisation_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'external_id' => 'p-2', 'first_name' => 'Bob', 'last_name' => 'Roe', 'organisation_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);
    DB::table($prefix.'clients')->insert([
        ['id' => 1, 'external_id' => 'c-1', 'name' => 'Acme', 'clientable_type' => V1_ORG_CLASS, 'clientable_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'external_id' => 'c-2', 'name' => 'Bob', 'clientable_type' => V1_PERSON_CLASS, 'clientable_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);

    foreach (['leads', 'deals', 'quotes', 'orders'] as $entity) {
        DB::table($prefix.$entity)->insert([
            ['id' => 1, 'external_id' => $entity.'-1', 'title' => ucfirst($entity).' One', 'client_id' => 1, 'organisation_id' => 1, 'person_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'external_id' => $entity.'-2', 'title' => ucfirst($entity).' Two', 'client_id' => 2, 'organisation_id' => 2, 'person_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    foreach (['invoices', 'purchase_orders', 'xero_contacts'] as $entity) {
        DB::table($prefix.$entity)->insert([
            ['id' => 1, 'external_id' => $entity.'-1', 'organisation_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    $polyTargets = [
        ['type' => V1_ORG_CLASS, 'id' => 1],
        ['type' => V1_CLIENT_CLASS, 'id' => 1],
        ['type' => V1_PERSON_CLASS, 'id' => 2],
    ];

    $insertMorph = function (string $table, string $morphName, array $extra = []) use ($polyTargets, $now) {
        $rows = [];
        $i = 1;
        foreach ($polyTargets as $t) {
            $rows[] = array_merge($extra, [
                'id' => $i++,
                $morphName.'_type' => $t['type'],
                $morphName.'_id' => $t['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table($table)->insert($rows);
    };

    $prefix = config('laravel-crm.db_table_prefix');
    $insertMorph($prefix.'emails', 'emailable', ['address' => 'a@b.test']);
    $insertMorph($prefix.'phones', 'phoneable', ['number' => '555-0100']);
    $insertMorph($prefix.'addresses', 'addressable', ['line1' => '1 Test St']);
    $insertMorph($prefix.'field_values', 'field_valueable', ['field_id' => 1, 'value' => 'x']);
    $insertMorph($prefix.'notes', 'noteable', ['content' => 'note']);
    $insertMorph($prefix.'files', 'fileable', ['filename' => 'f.txt']);

    $contactRows = [];
    $i = 1;
    foreach ($polyTargets as $t) {
        $contactRows[] = [
            'id' => $i,
            'contactable_type' => $t['type'], 'contactable_id' => $t['id'],
            'entityable_type' => $t['type'], 'entityable_id' => $t['id'],
            'created_at' => $now, 'updated_at' => $now,
        ];
        $i++;
    }
    DB::table($prefix.'contacts')->insert($contactRows);

    DB::table('audits')->insert([
        ['id' => 1, 'user_type' => V1_ORG_CLASS, 'user_id' => 1, 'event' => 'created', 'auditable_type' => V1_ORG_CLASS, 'auditable_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'user_type' => V1_CLIENT_CLASS, 'user_id' => 1, 'event' => 'updated', 'auditable_type' => V1_CLIENT_CLASS, 'auditable_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'user_type' => V1_PERSON_CLASS, 'user_id' => 2, 'event' => 'created', 'auditable_type' => V1_PERSON_CLASS, 'auditable_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('permissions')->insert([
        ['name' => 'view organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'create organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'edit organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'delete organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'view people', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $updateFlags = ['db_update_0180', 'db_update_0181', 'db_update_0191',
        'db_update_0193', 'db_update_0194', 'db_update_0199', 'db_update_1200'];
    foreach ($updateFlags as $flag) {
        DB::table($prefix.'settings')->insert([
            'name' => $flag, 'value' => '1', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}

function snapshotV1RowCounts(): array
{
    $prefix = config('laravel-crm.db_table_prefix');
    $tables = [
        $prefix.'organisations', $prefix.'organisation_types', $prefix.'clients',
        $prefix.'people', $prefix.'leads', $prefix.'deals', $prefix.'quotes', $prefix.'orders',
        $prefix.'invoices', $prefix.'purchase_orders', $prefix.'xero_contacts',
        $prefix.'emails', $prefix.'phones', $prefix.'addresses', $prefix.'field_values',
        $prefix.'notes', $prefix.'contacts', $prefix.'files', 'audits', 'permissions',
    ];
    $renames = [
        $prefix.'organisations' => $prefix.'organizations',
        $prefix.'organisation_types' => $prefix.'organization_types',
        $prefix.'clients' => $prefix.'customers',
    ];
    $out = [];
    foreach ($tables as $t) {
        $out[$renames[$t] ?? $t] = DB::table($t)->count();
    }

    return $out;
}

function snapshotV2RowCounts(): array
{
    $prefix = config('laravel-crm.db_table_prefix');
    $tables = [
        $prefix.'organizations', $prefix.'organization_types', $prefix.'customers',
        $prefix.'people', $prefix.'leads', $prefix.'deals', $prefix.'quotes', $prefix.'orders',
        $prefix.'invoices', $prefix.'purchase_orders', $prefix.'xero_contacts',
        $prefix.'emails', $prefix.'phones', $prefix.'addresses', $prefix.'field_values',
        $prefix.'notes', $prefix.'contacts', $prefix.'files', 'audits', 'permissions',
    ];
    $out = [];
    foreach ($tables as $t) {
        $out[$t] = DB::table($t)->count();
    }

    return $out;
}

function assertPolymorphTypeRewritten(string $table, string $column): void
{
    expect(DB::table($table)->where($column, V1_ORG_CLASS)->count())
        ->toBe(0, "Expected no v1 Organisation in {$table}.{$column}.");

    expect(DB::table($table)->where($column, V1_CLIENT_CLASS)->count())
        ->toBe(0, "Expected no v1 Client in {$table}.{$column}.");

    expect(
        DB::table($table)->where($column, V2_ORG_CLASS)->count() +
        DB::table($table)->where($column, V2_CUSTOMER_CLASS)->count()
    )->toBeGreaterThan(0, "Expected at least one v2 Organization/Customer in {$table}.{$column}.");
}

// --------------------------------------------------------------------
// Tests
// --------------------------------------------------------------------

test('v2 upgrade renames tables columns permissions and polymorphic types', function () {
    seedV1Data();

    $countsBefore = snapshotV1RowCounts();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->assertExitCode(0);

    $prefix = config('laravel-crm.db_table_prefix');

    expect(Schema::hasTable($prefix.'organisations'))->toBeFalse();
    expect(Schema::hasTable($prefix.'organisation_types'))->toBeFalse();
    expect(Schema::hasTable($prefix.'clients'))->toBeFalse();
    expect(Schema::hasTable($prefix.'organizations'))->toBeTrue();
    expect(Schema::hasTable($prefix.'organization_types'))->toBeTrue();
    expect(Schema::hasTable($prefix.'customers'))->toBeTrue();

    foreach (['people', 'leads', 'deals', 'quotes', 'orders', 'invoices', 'purchase_orders', 'xero_contacts'] as $t) {
        expect(Schema::hasColumn($prefix.$t, 'organization_id'))->toBeTrue("Expected {$prefix}{$t}.organization_id");
        expect(Schema::hasColumn($prefix.$t, 'organisation_id'))->toBeFalse("Expected {$prefix}{$t}.organisation_id to be removed");
    }

    expect(Schema::hasColumn($prefix.'organizations', 'organization_type_id'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'organizations', 'organisation_type_id'))->toBeFalse();

    foreach (['leads', 'deals', 'quotes', 'orders'] as $t) {
        expect(Schema::hasColumn($prefix.$t, 'customer_id'))->toBeTrue();
        expect(Schema::hasColumn($prefix.$t, 'client_id'))->toBeFalse();
    }

    expect(Schema::hasColumn($prefix.'customers', 'customerable_type'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'customers', 'customerable_id'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'customers', 'clientable_type'))->toBeFalse();
    expect(Schema::hasColumn($prefix.'customers', 'clientable_id'))->toBeFalse();

    assertPolymorphTypeRewritten($prefix.'emails', 'emailable_type');
    assertPolymorphTypeRewritten($prefix.'phones', 'phoneable_type');
    assertPolymorphTypeRewritten($prefix.'addresses', 'addressable_type');
    assertPolymorphTypeRewritten($prefix.'field_values', 'field_valueable_type');
    assertPolymorphTypeRewritten($prefix.'notes', 'noteable_type');
    assertPolymorphTypeRewritten($prefix.'contacts', 'contactable_type');
    assertPolymorphTypeRewritten($prefix.'contacts', 'entityable_type');
    assertPolymorphTypeRewritten($prefix.'files', 'fileable_type');
    assertPolymorphTypeRewritten($prefix.'customers', 'customerable_type');
    assertPolymorphTypeRewritten('audits', 'auditable_type');
    assertPolymorphTypeRewritten('audits', 'user_type');

    expect(DB::table($prefix.'customers')->where('customerable_type', V1_PERSON_CLASS)->count())
        ->toBe(1, 'Person customerable_type rows should not be rewritten.');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view', 'create', 'edit', 'delete'] as $verb) {
        expect(Permission::where('name', "{$verb} organizations")->exists())->toBeTrue();
        expect(Permission::where('name', "{$verb} organisations")->exists())->toBeFalse();
    }
    expect(Permission::where('name', 'view people')->exists())->toBeTrue();

    $countsAfter = snapshotV2RowCounts();
    foreach ($countsBefore as $key => $count) {
        expect($countsAfter[$key] ?? null)->toBe($count, "Row count drift on {$key} after upgrade.");
    }
});

test('v2 upgrade is idempotent', function () {
    seedV1Data();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->assertExitCode(0);

    $snapshot = snapshotV2RowCounts();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->assertExitCode(0);

    expect(snapshotV2RowCounts())->toBe($snapshot, 'Second upgrade run mutated data.');

    $prefix = config('laravel-crm.db_table_prefix');
    expect(Schema::hasTable($prefix.'organizations'))->toBeTrue();
    expect(Schema::hasTable($prefix.'customers'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'leads', 'organization_id'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'leads', 'customer_id'))->toBeTrue();
});

test('v2 upgrade aborts when user declines backup confirmation', function () {
    seedV1Data();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'no')
        ->expectsOutputToContain('Aborted')
        ->assertExitCode(0);

    $prefix = config('laravel-crm.db_table_prefix');

    expect(Schema::hasTable($prefix.'organisations'))->toBeTrue();
    expect(Schema::hasTable($prefix.'clients'))->toBeTrue();
    expect(Schema::hasTable($prefix.'organizations'))->toBeFalse();
    expect(Schema::hasTable($prefix.'customers'))->toBeFalse();
    expect(Permission::where('name', 'view organisations')->exists())->toBeTrue();
});
