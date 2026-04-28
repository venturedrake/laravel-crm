<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use VentureDrake\LaravelCrm\Models\Permission;
use VentureDrake\LaravelCrm\Tests\Stubs\V1Schema;
use VentureDrake\LaravelCrm\Tests\TestCase;

/**
 * End-to-end test of the v1 → v2 upgrade pipeline driven by `laravelcrm:v2`.
 *
 * Strategy:
 *   1. Boot a SQLite in-memory DB with a hand-built v1-shaped schema (V1Schema).
 *   2. Seed representative v1 data using raw DB::table() inserts so we are
 *      independent of any v1 model classes (which no longer exist on master).
 *   3. Run `laravelcrm:v2` (auto-confirming the backup prompt).
 *   4. Assert tables/columns/polymorphic types/permissions are rewritten
 *      and that no row is lost.
 *   5. Run the command a second time and assert the upgrade is idempotent.
 *
 * The optional "run v2 migrations afterwards" leg is intentionally skipped:
 * v2 migrations ship as `.stub` files and are not executed by Laravel's
 * migrator in tests. The schema-level assertions below are sufficient to
 * prove the upgrade contract honoured by LaravelCrmV2::handle().
 *
 * @group upgrade
 */
class V1ToV2UpgradeTest extends TestCase
{
    private const V1_ORG_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Organisation';

    private const V1_CLIENT_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Client';

    private const V1_PERSON_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Person';

    private const V2_ORG_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Organization';

    private const V2_CUSTOMER_CLASS = 'VentureDrake\\LaravelCrm\\Models\\Customer';

    /**
     * Override TestCase::defineDatabaseMigrations() so we boot the v1 schema
     * instead of the v2 TestSchema.
     */
    protected function defineDatabaseMigrations()
    {
        V1Schema::up();
    }

    public function test_v2_upgrade_renames_tables_columns_permissions_and_polymorphic_types(): void
    {
        $this->seedV1Data();

        // Snapshot row counts so we can assert no data is lost.
        $countsBefore = $this->snapshotV1RowCounts();

        $this->artisan('laravelcrm:v2')
            ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
            ->assertExitCode(0);

        $prefix = config('laravel-crm.db_table_prefix');

        // --- Tables renamed
        $this->assertFalse(Schema::hasTable($prefix.'organisations'));
        $this->assertFalse(Schema::hasTable($prefix.'organisation_types'));
        $this->assertFalse(Schema::hasTable($prefix.'clients'));
        $this->assertTrue(Schema::hasTable($prefix.'organizations'));
        $this->assertTrue(Schema::hasTable($prefix.'organization_types'));
        $this->assertTrue(Schema::hasTable($prefix.'customers'));

        // --- Columns renamed: organisation_id -> organization_id
        foreach (['people', 'leads', 'deals', 'quotes', 'orders', 'invoices', 'purchase_orders', 'xero_contacts'] as $t) {
            $this->assertTrue(
                Schema::hasColumn($prefix.$t, 'organization_id'),
                "Expected {$prefix}{$t}.organization_id to exist after upgrade."
            );
            $this->assertFalse(
                Schema::hasColumn($prefix.$t, 'organisation_id'),
                "Expected {$prefix}{$t}.organisation_id to be removed."
            );
        }

        // --- organisation_type_id -> organization_type_id on organizations
        $this->assertTrue(Schema::hasColumn($prefix.'organizations', 'organization_type_id'));
        $this->assertFalse(Schema::hasColumn($prefix.'organizations', 'organisation_type_id'));

        // --- client_id -> customer_id
        foreach (['leads', 'deals', 'quotes', 'orders'] as $t) {
            $this->assertTrue(Schema::hasColumn($prefix.$t, 'customer_id'));
            $this->assertFalse(Schema::hasColumn($prefix.$t, 'client_id'));
        }

        // --- clientable_* -> customerable_* on customers
        $this->assertTrue(Schema::hasColumn($prefix.'customers', 'customerable_type'));
        $this->assertTrue(Schema::hasColumn($prefix.'customers', 'customerable_id'));
        $this->assertFalse(Schema::hasColumn($prefix.'customers', 'clientable_type'));
        $this->assertFalse(Schema::hasColumn($prefix.'customers', 'clientable_id'));

        // --- Polymorphic type strings rewritten
        $this->assertPolymorphTypeRewritten($prefix.'emails', 'emailable_type');
        $this->assertPolymorphTypeRewritten($prefix.'phones', 'phoneable_type');
        $this->assertPolymorphTypeRewritten($prefix.'addresses', 'addressable_type');
        $this->assertPolymorphTypeRewritten($prefix.'field_values', 'field_valueable_type');
        $this->assertPolymorphTypeRewritten($prefix.'notes', 'noteable_type');
        $this->assertPolymorphTypeRewritten($prefix.'contacts', 'contactable_type');
        $this->assertPolymorphTypeRewritten($prefix.'contacts', 'entityable_type');
        $this->assertPolymorphTypeRewritten($prefix.'files', 'fileable_type');
        $this->assertPolymorphTypeRewritten($prefix.'customers', 'customerable_type');
        $this->assertPolymorphTypeRewritten('audits', 'auditable_type');
        $this->assertPolymorphTypeRewritten('audits', 'user_type');

        // --- Person polymorph values must NOT be touched
        $this->assertSame(
            1,
            DB::table($prefix.'customers')->where('customerable_type', self::V1_PERSON_CLASS)->count(),
            'Person customerable_type rows should not be rewritten.'
        );

        // --- Permissions renamed
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view', 'create', 'edit', 'delete'] as $verb) {
            $this->assertTrue(
                Permission::where('name', "{$verb} organizations")->exists(),
                "Expected permission '{$verb} organizations' to exist."
            );
            $this->assertFalse(
                Permission::where('name', "{$verb} organisations")->exists(),
                "Expected permission '{$verb} organisations' to be renamed."
            );
        }
        // Control permission untouched
        $this->assertTrue(Permission::where('name', 'view people')->exists());

        // --- No row counts changed across rename
        $countsAfter = $this->snapshotV2RowCounts();
        foreach ($countsBefore as $key => $count) {
            $this->assertSame(
                $count,
                $countsAfter[$key] ?? null,
                "Row count drift on {$key} after upgrade."
            );
        }
    }

    public function test_v2_upgrade_is_idempotent(): void
    {
        $this->seedV1Data();

        $this->artisan('laravelcrm:v2')
            ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
            ->assertExitCode(0);

        $snapshot = $this->snapshotV2RowCounts();

        // Second run: tables/columns already migrated, polymorph values already rewritten.
        $this->artisan('laravelcrm:v2')
            ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
            ->assertExitCode(0);

        $this->assertSame($snapshot, $this->snapshotV2RowCounts(), 'Second upgrade run mutated data.');

        // Spot-check schema is still v2.
        $prefix = config('laravel-crm.db_table_prefix');
        $this->assertTrue(Schema::hasTable($prefix.'organizations'));
        $this->assertTrue(Schema::hasTable($prefix.'customers'));
        $this->assertTrue(Schema::hasColumn($prefix.'leads', 'organization_id'));
        $this->assertTrue(Schema::hasColumn($prefix.'leads', 'customer_id'));
    }

    public function test_v2_upgrade_aborts_when_user_declines_backup_confirmation(): void
    {
        $this->seedV1Data();

        $this->artisan('laravelcrm:v2')
            ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'no')
            ->expectsOutputToContain('Aborted')
            ->assertExitCode(0);

        $prefix = config('laravel-crm.db_table_prefix');

        // Schema must still be v1 — no renames performed.
        $this->assertTrue(Schema::hasTable($prefix.'organisations'));
        $this->assertTrue(Schema::hasTable($prefix.'clients'));
        $this->assertFalse(Schema::hasTable($prefix.'organizations'));
        $this->assertFalse(Schema::hasTable($prefix.'customers'));
        $this->assertTrue(Permission::where('name', 'view organisations')->exists());
    }

    // --------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------

    private function seedV1Data(): void
    {
        $prefix = config('laravel-crm.db_table_prefix');
        $now = now();

        // Organisation types + organisations
        DB::table($prefix.'organisation_types')->insert([
            ['id' => 1, 'name' => 'Customer', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Supplier', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table($prefix.'organisations')->insert([
            ['id' => 1, 'external_id' => 'org-1', 'name' => 'Acme Inc', 'organisation_type_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'external_id' => 'org-2', 'name' => 'Globex Ltd', 'organisation_type_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // People
        DB::table($prefix.'people')->insert([
            ['id' => 1, 'external_id' => 'p-1', 'first_name' => 'Alice', 'last_name' => 'Doe', 'organisation_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'external_id' => 'p-2', 'first_name' => 'Bob', 'last_name' => 'Roe', 'organisation_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Clients (one organisation-backed, one person-backed)
        DB::table($prefix.'clients')->insert([
            ['id' => 1, 'external_id' => 'c-1', 'name' => 'Acme', 'clientable_type' => self::V1_ORG_CLASS, 'clientable_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'external_id' => 'c-2', 'name' => 'Bob', 'clientable_type' => self::V1_PERSON_CLASS, 'clientable_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Entity tables with both client_id + organisation_id
        foreach (['leads', 'deals', 'quotes', 'orders'] as $entity) {
            DB::table($prefix.$entity)->insert([
                ['id' => 1, 'external_id' => $entity.'-1', 'title' => ucfirst($entity).' One', 'client_id' => 1, 'organisation_id' => 1, 'person_id' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'external_id' => $entity.'-2', 'title' => ucfirst($entity).' Two', 'client_id' => 2, 'organisation_id' => 2, 'person_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // organisation_id only
        foreach (['invoices', 'purchase_orders', 'xero_contacts'] as $entity) {
            DB::table($prefix.$entity)->insert([
                ['id' => 1, 'external_id' => $entity.'-1', 'organisation_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // Polymorphic relations: one Organisation row + one Client row + one untouched Person row each
        $morph = function (string $type, int $id) {
            return ['type' => $type, 'id' => $id];
        };
        $polyTargets = [
            $morph(self::V1_ORG_CLASS, 1),
            $morph(self::V1_CLIENT_CLASS, 1),
            $morph(self::V1_PERSON_CLASS, 2),
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

        $insertMorph($prefix.'emails', 'emailable', ['address' => 'a@b.test']);
        $insertMorph($prefix.'phones', 'phoneable', ['number' => '555-0100']);
        $insertMorph($prefix.'addresses', 'addressable', ['line1' => '1 Test St']);
        $insertMorph($prefix.'field_values', 'field_valueable', ['field_id' => 1, 'value' => 'x']);
        $insertMorph($prefix.'notes', 'noteable', ['content' => 'note']);
        $insertMorph($prefix.'files', 'fileable', ['filename' => 'f.txt']);

        // contacts: both contactable_* and entityable_*
        $contactRows = [];
        $i = 1;
        foreach ($polyTargets as $t) {
            $contactRows[] = [
                'id' => $i,
                'contactable_type' => $t['type'],
                'contactable_id' => $t['id'],
                'entityable_type' => $t['type'],
                'entityable_id' => $t['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $i++;
        }
        DB::table($prefix.'contacts')->insert($contactRows);

        // audits: auditable_type + user_type both target rewritable classes
        DB::table('audits')->insert([
            ['id' => 1, 'user_type' => self::V1_ORG_CLASS, 'user_id' => 1, 'event' => 'created', 'auditable_type' => self::V1_ORG_CLASS, 'auditable_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'user_type' => self::V1_CLIENT_CLASS, 'user_id' => 1, 'event' => 'updated', 'auditable_type' => self::V1_CLIENT_CLASS, 'auditable_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'user_type' => self::V1_PERSON_CLASS, 'user_id' => 2, 'event' => 'created', 'auditable_type' => self::V1_PERSON_CLASS, 'auditable_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Permissions
        DB::table('permissions')->insert([
            ['name' => 'view organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'create organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'edit organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'delete organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'view people', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Snapshot row counts using v1 table names. Used pre-upgrade.
     *
     * @return array<string,int>
     */
    private function snapshotV1RowCounts(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');

        $tables = [
            $prefix.'organisations',
            $prefix.'organisation_types',
            $prefix.'clients',
            $prefix.'people',
            $prefix.'leads',
            $prefix.'deals',
            $prefix.'quotes',
            $prefix.'orders',
            $prefix.'invoices',
            $prefix.'purchase_orders',
            $prefix.'xero_contacts',
            $prefix.'emails',
            $prefix.'phones',
            $prefix.'addresses',
            $prefix.'field_values',
            $prefix.'notes',
            $prefix.'contacts',
            $prefix.'files',
            'audits',
            'permissions',
        ];

        $renames = [
            $prefix.'organisations' => $prefix.'organizations',
            $prefix.'organisation_types' => $prefix.'organization_types',
            $prefix.'clients' => $prefix.'customers',
        ];

        $out = [];
        foreach ($tables as $t) {
            $key = $renames[$t] ?? $t;
            $out[$key] = DB::table($t)->count();
        }

        return $out;
    }

    /**
     * Snapshot row counts using v2 table names. Used post-upgrade.
     *
     * @return array<string,int>
     */
    private function snapshotV2RowCounts(): array
    {
        $prefix = config('laravel-crm.db_table_prefix');

        $tables = [
            $prefix.'organizations',
            $prefix.'organization_types',
            $prefix.'customers',
            $prefix.'people',
            $prefix.'leads',
            $prefix.'deals',
            $prefix.'quotes',
            $prefix.'orders',
            $prefix.'invoices',
            $prefix.'purchase_orders',
            $prefix.'xero_contacts',
            $prefix.'emails',
            $prefix.'phones',
            $prefix.'addresses',
            $prefix.'field_values',
            $prefix.'notes',
            $prefix.'contacts',
            $prefix.'files',
            'audits',
            'permissions',
        ];

        $out = [];
        foreach ($tables as $t) {
            $out[$t] = DB::table($t)->count();
        }

        return $out;
    }

    /**
     * Assert that v1 polymorphic class strings have been rewritten to v2 strings
     * on the given table/column, and that no v1 strings remain.
     */
    private function assertPolymorphTypeRewritten(string $table, string $column): void
    {
        $this->assertSame(
            0,
            DB::table($table)->where($column, self::V1_ORG_CLASS)->count(),
            "Expected no v1 Organisation in {$table}.{$column}."
        );
        $this->assertSame(
            0,
            DB::table($table)->where($column, self::V1_CLIENT_CLASS)->count(),
            "Expected no v1 Client in {$table}.{$column}."
        );
        $this->assertGreaterThan(
            0,
            DB::table($table)->where($column, self::V2_ORG_CLASS)->count() +
            DB::table($table)->where($column, self::V2_CUSTOMER_CLASS)->count(),
            "Expected at least one v2 Organization/Customer in {$table}.{$column}."
        );
    }
}
