<?php

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use VentureDrake\LaravelCrm\Console\LaravelCrmV2;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Models\Permission;
use VentureDrake\LaravelCrm\Services\SystemCheckService;
use VentureDrake\LaravelCrm\Tests\Stubs\V1Schema;

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

    // The four types the v1 seeder created. The v2 seeder keys on a setting v1
    // never wrote, so it re-runs firstOrCreate by id and must find them all.
    DB::table($prefix.'organisation_types')->insert([
        ['id' => 1, 'name' => 'Sole Trader', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'Partnership', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'name' => 'Company', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 4, 'name' => 'Trust', 'created_at' => $now, 'updated_at' => $now],
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
        // Orders have no title column.
        $label = $entity === 'orders' ? 'description' : 'title';

        DB::table($prefix.$entity)->insert([
            ['id' => 1, 'external_id' => $entity.'-1', $label => ucfirst($entity).' One', 'client_id' => 1, 'organisation_id' => 1, 'person_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'external_id' => $entity.'-2', $label => ucfirst($entity).' Two', 'client_id' => 2, 'organisation_id' => 2, 'person_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    DB::table($prefix.'invoices')->insert([
        ['id' => 1, 'external_id' => 'invoices-1', 'invoice_id' => 'INV1001', 'organisation_id' => 1, 'created_at' => $now, 'updated_at' => $now],
    ]);

    foreach (['purchase_orders', 'xero_contacts'] as $entity) {
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

    $insertMorph($prefix.'emails', 'emailable', ['address' => 'a@b.test']);
    $insertMorph($prefix.'phones', 'phoneable', ['number' => '555-0100']);
    $insertMorph($prefix.'addresses', 'addressable', ['line1' => '1 Test St']);
    $insertMorph($prefix.'field_values', 'field_valueable', ['field_id' => 1, 'value' => 'x']);
    $insertMorph($prefix.'notes', 'noteable', ['content' => 'note']);
    $insertMorph($prefix.'files', 'fileable', ['file' => 'f.txt']);
    $insertMorph($prefix.'tasks', 'taskable', ['name' => 'Task']);
    $insertMorph($prefix.'calls', 'callable', ['name' => 'Call']);
    $insertMorph($prefix.'meetings', 'meetingable', ['name' => 'Meeting']);
    $insertMorph($prefix.'lunches', 'lunchable', ['name' => 'Lunch']);
    $insertMorph('audits', 'auditable', ['event' => 'updated']);

    // labelables has no id or timestamps, and its morph columns carry the table prefix.
    foreach ($polyTargets as $t) {
        DB::table($prefix.'labelables')->insert([
            'label_id' => 1, $prefix.'labelable_type' => $t['type'], $prefix.'labelable_id' => $t['id'],
        ]);
    }

    // Activities carry three morphs; point all three at the same targets.
    $i = 1;
    foreach ($polyTargets as $t) {
        DB::table($prefix.'activities')->insert([
            'id' => $i++,
            'timelineable_type' => $t['type'], 'timelineable_id' => $t['id'],
            'recordable_type' => $t['type'], 'recordable_id' => $t['id'],
            'causeable_type' => $t['type'], 'causeable_id' => $t['id'],
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    $i = 1;
    foreach ($polyTargets as $t) {
        DB::table($prefix.'field_models')->insert([
            'id' => $i++, 'field_id' => 1, 'model' => $t['type'], 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

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

    DB::table('permissions')->insert([
        ['name' => 'view organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'create organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'edit organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'delete organisations', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'view people', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $updateFlags = ['db_update_0180', 'db_update_0181', 'db_update_0191',
        'db_update_0193', 'db_update_0194', 'db_update_0199', 'db_update_1200',
        'db_update_1201'];
    foreach ($updateFlags as $flag) {
        DB::table($prefix.'settings')->insert([
            'name' => $flag, 'value' => '1', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}

/**
 * Every column that persists a CRM model class name, by its v2 table name.
 *
 * @return list<array{0: string, 1: string}>
 */
function classNameColumns(): array
{
    $prefix = config('laravel-crm.db_table_prefix');

    return [
        [$prefix.'emails', 'emailable_type'],
        [$prefix.'phones', 'phoneable_type'],
        [$prefix.'addresses', 'addressable_type'],
        [$prefix.'field_values', 'field_valueable_type'],
        [$prefix.'notes', 'noteable_type'],
        [$prefix.'contacts', 'contactable_type'],
        [$prefix.'contacts', 'entityable_type'],
        [$prefix.'files', 'fileable_type'],
        [$prefix.'customers', 'customerable_type'],
        [$prefix.'labelables', $prefix.'labelable_type'],
        [$prefix.'tasks', 'taskable_type'],
        [$prefix.'calls', 'callable_type'],
        [$prefix.'meetings', 'meetingable_type'],
        [$prefix.'lunches', 'lunchable_type'],
        [$prefix.'activities', 'timelineable_type'],
        [$prefix.'activities', 'recordable_type'],
        [$prefix.'activities', 'causeable_type'],
        [$prefix.'field_models', 'model'],
        ['audits', 'auditable_type'],
    ];
}

/**
 * Tables the upgrade must not add rows to or remove rows from, by their v1 name.
 *
 * `permissions` is left out: the nested laravelcrm:update seeds the v2
 * permission set, so the count grows by design. The renamed v1 permissions
 * are asserted on separately.
 *
 * @return list<string>
 */
function v1DataTables(): array
{
    $prefix = config('laravel-crm.db_table_prefix');

    return [
        $prefix.'organisations', $prefix.'organisation_types', $prefix.'clients',
        $prefix.'people', $prefix.'leads', $prefix.'deals', $prefix.'quotes', $prefix.'orders',
        $prefix.'invoices', $prefix.'purchase_orders', $prefix.'xero_contacts',
        $prefix.'emails', $prefix.'phones', $prefix.'addresses', $prefix.'field_values',
        $prefix.'notes', $prefix.'contacts', $prefix.'files',
        $prefix.'labelables', $prefix.'tasks', $prefix.'calls', $prefix.'meetings', $prefix.'lunches',
        $prefix.'activities', $prefix.'field_models', 'audits',
    ];
}

function snapshotV1RowCounts(): array
{
    $prefix = config('laravel-crm.db_table_prefix');
    $tables = v1DataTables();
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
    $renames = [
        $prefix.'organisations' => $prefix.'organizations',
        $prefix.'organisation_types' => $prefix.'organization_types',
        $prefix.'clients' => $prefix.'customers',
    ];
    $out = [];
    foreach (v1DataTables() as $t) {
        $t = $renames[$t] ?? $t;
        $out[$t] = DB::table($t)->count();
    }
    $out['permissions'] = DB::table('permissions')->count();

    return $out;
}

/**
 * The class-name columns' contents, for comparing two upgrade runs.
 */
function snapshotClassNames(): array
{
    $out = [];
    foreach (classNameColumns() as [$table, $column]) {
        $out["{$table}.{$column}"] = DB::table($table)->orderBy($column)->pluck($column)->all();
    }

    return $out;
}

/**
 * The published file name (without .php) of a migration stub, as the
 * provider resolves it.
 */
function publishedMigrationName(string $suffix): string
{
    $name = collect(ServiceProvider::pathsToPublish(LaravelCrmServiceProvider::class, 'migrations'))
        ->map(fn ($path) => basename($path, '.php'))
        ->first(fn ($name) => str_ends_with($name, '_'.$suffix));

    expect($name)->not->toBeNull("No published migration ends in {$suffix}.");

    return $name;
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

    $this->artisan('laravelcrm:v2', ['--force' => true])
        ->doesntExpectOutputToContain('has NOT been updated')
        ->expectsOutputToContain('Laravel CRM is now updated to version 2.')
        ->assertExitCode(0);

    $prefix = config('laravel-crm.db_table_prefix');

    // The v2 names of the renamed v1 migrations are recorded in the v1 rows'
    // batch, so the nested migrate does not run them again.
    $batches = app('migration.repository')->getMigrationBatches();
    foreach (LaravelCrmV2::RENAMED_MIGRATIONS as $v2) {
        expect($batches[publishedMigrationName($v2)] ?? null)->toBe(1, "Expected {$v2} to be marked as run in batch 1.");
    }

    // The deals pair is deliberately not reconciled: its v2 migration really ran.
    expect($batches[publishedMigrationName('add_customer_to_laravel_crm_deals_table')] ?? null)->toBeGreaterThan(1);

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

    foreach (classNameColumns() as [$table, $column]) {
        assertPolymorphTypeRewritten($table, $column);

        expect(DB::table($table)->where($column, V1_PERSON_CLASS)->count())
            ->toBe(1, "Person rows in {$table}.{$column} should not be rewritten.");
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view', 'create', 'edit', 'delete'] as $verb) {
        expect(Permission::where('name', "{$verb} organizations")->exists())->toBeTrue();
        expect(Permission::where('name', "{$verb} organisations")->exists())->toBeFalse();
    }
    expect(Permission::where('name', 'view people')->exists())->toBeTrue();

    // A completed upgrade owes no further db updates: every flag the v1 host
    // carried is still applied, and db_update_1201 -- which the upgrade itself
    // runs -- has been marked too.
    foreach (array_keys(SystemCheckService::DB_UPDATES) as $flag) {
        $value = DB::table($prefix.'settings')->where('name', $flag)->value('value');

        expect($value)->not->toBeNull("Expected {$flag} to exist after the upgrade.")
            ->and((int) $value)->toBe(1, "Expected {$flag} to be applied after the upgrade.");
    }

    $countsAfter = snapshotV2RowCounts();
    foreach ($countsBefore as $key => $count) {
        expect($countsAfter[$key] ?? null)->toBe($count, "Row count drift on {$key} after upgrade.");
    }
});

test('the v2 extra-fields migration skips the columns a v1 host already has', function () {
    // Without the v1 row there is nothing to reconcile, so the v2 migration
    // runs against an organizations table that already has all 16 columns.
    DB::table('migrations')->where('migration', V1Schema::V1_EXTRA_FIELDS_MIGRATION)->delete();
    seedV1Data();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->doesntExpectOutputToContain('has NOT been updated')
        ->assertExitCode(0);

    $batches = app('migration.repository')->getMigrationBatches();
    expect($batches[publishedMigrationName('add_extra_fields_to_laravel_crm_organizations_table')] ?? null)->toBeGreaterThan(1);

    $prefix = config('laravel-crm.db_table_prefix');
    foreach (['vat_number', 'domain', 'year_founded', 'timezone_id', 'google'] as $column) {
        expect(Schema::hasColumn($prefix.'organizations', $column))->toBeTrue();
    }
});

test('v2 upgrade is idempotent', function () {
    seedV1Data();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->assertExitCode(0);

    $counts = snapshotV2RowCounts();
    $migrations = DB::table('migrations')->orderBy('migration')->get(['migration', 'batch'])->all();
    $classNames = snapshotClassNames();

    $this->artisan('laravelcrm:v2', ['--force' => true])
        ->doesntExpectOutputToContain('has NOT been updated')
        ->assertExitCode(0);

    expect(snapshotV2RowCounts())->toBe($counts, 'Second upgrade run mutated data.')
        ->and(DB::table('migrations')->orderBy('migration')->get(['migration', 'batch'])->all())->toEqual($migrations)
        ->and(snapshotClassNames())->toBe($classNames);

    $prefix = config('laravel-crm.db_table_prefix');
    expect(Schema::hasTable($prefix.'organizations'))->toBeTrue();
    expect(Schema::hasTable($prefix.'customers'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'leads', 'organization_id'))->toBeTrue();
    expect(Schema::hasColumn($prefix.'leads', 'customer_id'))->toBeTrue();
});

/**
 * The state a v2 upgrade that changed nothing leaves behind.
 */
function assertStillOnV1(): void
{
    $prefix = config('laravel-crm.db_table_prefix');

    expect(Schema::hasTable($prefix.'organisations'))->toBeTrue();
    expect(Schema::hasTable($prefix.'clients'))->toBeTrue();
    expect(Schema::hasTable($prefix.'organizations'))->toBeFalse();
    expect(Schema::hasTable($prefix.'customers'))->toBeFalse();
    expect(Permission::where('name', 'view organisations')->exists())->toBeTrue();
}

test('v2 upgrade aborts when user declines backup confirmation', function () {
    seedV1Data();

    // A non-zero exit, deliberately: nothing was upgraded, and a deploy
    // script chaining `laravelcrm:v2 && ...` must not carry on as if it had.
    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'no')
        ->expectsOutputToContain('Aborted')
        ->assertExitCode(1);

    assertStillOnV1();
});

test('v2 upgrade refuses to run unattended without --force', function () {
    seedV1Data();

    $this->artisan('laravelcrm:v2', ['--no-interaction' => true])
        ->expectsOutputToContain('--force')
        ->assertExitCode(1);

    assertStillOnV1();
});

test('v2 upgrade fails when the nested laravelcrm:update fails', function () {
    seedV1Data();

    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'crm-failing-migration-'.uniqid();
    mkdir($path);
    file_put_contents($path.'/2099_01_01_000000_fail_the_crm_v2_upgrade.php', <<<'PHP'
        <?php

        use Illuminate\Database\Migrations\Migration;

        return new class extends Migration
        {
            public function up(): void
            {
                throw new RuntimeException('Simulated migration failure');
            }
        };
        PHP);

    app('migrator')->path($path);

    try {
        $this->artisan('laravelcrm:v2', ['--force' => true])
            ->expectsOutputToContain('Simulated migration failure')
            ->expectsOutputToContain('laravelcrm:update did not finish')
            ->doesntExpectOutputToContain('now updated to version 2')
            ->assertExitCode(1);
    } finally {
        (new Filesystem)->deleteDirectory($path);
    }
});

// --------------------------------------------------------------------
// Running v2 migrations on a v1 database
// --------------------------------------------------------------------

/**
 * Laravel only turns Symfony's console events into CommandStarting outside
 * unit tests. Switch it on, and drop the cached Artisan application so the
 * next call is built with the dispatcher.
 */
function rerouteCommandEvents(): void
{
    $kernel = app(Kernel::class);
    $kernel->rerouteSymfonyCommandEvents();
    $kernel->setArtisan(null);
}

/**
 * Strand the v1 data the way 2.5.0 allowed: run `migrate` on the v1 database
 * before laravelcrm:v2. The guard is not listening (command events are not
 * rerouted), so this creates empty v2 tables and NULL v2 columns next to the
 * v1 ones.
 */
function strandV1Data(): void
{
    Artisan::call('vendor:publish', [
        '--provider' => LaravelCrmServiceProvider::class,
        '--tag' => 'migrations',
        '--force' => true,
    ]);

    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0);

    $prefix = config('laravel-crm.db_table_prefix');
    expect(Schema::hasTable($prefix.'customers'))->toBeTrue()
        ->and(Schema::hasTable($prefix.'organization_types'))->toBeTrue()
        ->and(Schema::hasColumn($prefix.'leads', 'customer_id'))->toBeTrue();
}

foreach (['migrate', 'laravelcrm:update'] as $guardedCommand) {
    test("{$guardedCommand} refuses to run on a v1 database", function () use ($guardedCommand) {
        seedV1Data();
        rerouteCommandEvents();

        $migrationsBefore = DB::table('migrations')->count();

        expect(fn () => Artisan::call($guardedCommand, ['--force' => true]))
            ->toThrow(RuntimeException::class, 'run `php artisan laravelcrm:v2` first');

        $prefix = config('laravel-crm.db_table_prefix');
        expect(Schema::hasTable($prefix.'organizations'))->toBeFalse()
            ->and(Schema::hasTable($prefix.'customers'))->toBeFalse()
            ->and(Schema::hasTable($prefix.'organization_types'))->toBeFalse()
            ->and(DB::table('migrations')->count())->toBe($migrationsBefore);
    });
}

test('the v1 guard leaves other commands alone', function () {
    rerouteCommandEvents();

    expect(Artisan::call('migrate:status'))->toBe(0);
});

test('the v1 guard does nothing before there is a migrations table', function () {
    Schema::drop('migrations');

    event(new CommandStarting('migrate', new ArrayInput([]), new NullOutput));

    expect(Schema::hasTable('migrations'))->toBeFalse();
});

test('the v1 guard does not block the migrations laravelcrm:v2 runs itself', function () {
    seedV1Data();
    rerouteCommandEvents();

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->doesntExpectOutputToContain('has NOT been updated')
        ->assertExitCode(0);

    $prefix = config('laravel-crm.db_table_prefix');
    expect(Schema::hasTable($prefix.'organisations'))->toBeFalse()
        ->and(Schema::hasTable($prefix.'organizations'))->toBeTrue();
});

test('v2 upgrade heals empty v2 tables and columns created by an early migrate', function () {
    seedV1Data();
    strandV1Data();

    $prefix = config('laravel-crm.db_table_prefix');

    // An index on the stranded column, which has to go before the column can.
    Schema::table($prefix.'leads', fn (Blueprint $table) => $table->index('customer_id'));

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->expectsOutputToContain("Dropping empty v2 table: {$prefix}customers")
        ->expectsOutputToContain("Dropping empty v2 table: {$prefix}organization_types")
        ->expectsOutputToContain("Dropping empty v2 column: {$prefix}leads.customer_id")
        ->doesntExpectOutputToContain('has NOT been updated')
        ->assertExitCode(0);

    expect(Schema::hasTable($prefix.'clients'))->toBeFalse()
        ->and(Schema::hasTable($prefix.'organisations'))->toBeFalse()
        ->and(Schema::hasTable($prefix.'organisation_types'))->toBeFalse()
        ->and(DB::table($prefix.'customers')->count())->toBe(2)
        ->and(DB::table($prefix.'organization_types')->count())->toBe(4)
        ->and(DB::table($prefix.'organizations')->count())->toBe(2);

    foreach (['leads', 'deals', 'quotes', 'orders'] as $t) {
        expect(Schema::hasColumn($prefix.$t, 'client_id'))->toBeFalse()
            ->and(DB::table($prefix.$t)->orderBy('id')->pluck('customer_id')->all())->toBe([1, 2]);
    }

    assertPolymorphTypeRewritten($prefix.'customers', 'customerable_type');
});

test('v2 upgrade stops when a stranded v2 table has rows', function () {
    seedV1Data();
    strandV1Data();

    $prefix = config('laravel-crm.db_table_prefix');
    DB::table($prefix.'customers')->insert([
        'external_id' => 'v2-1', 'customerable_type' => V2_ORG_CLASS, 'customerable_id' => 1,
    ]);

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->expectsOutputToContain("Both {$prefix}clients (2 rows) and {$prefix}customers (1 rows) exist.")
        ->expectsOutputToContain('Nothing has been changed')
        ->assertExitCode(1);

    expect(Schema::hasTable($prefix.'clients'))->toBeTrue()
        ->and(Schema::hasTable($prefix.'organisations'))->toBeTrue()
        ->and(Schema::hasColumn($prefix.'leads', 'client_id'))->toBeTrue()
        ->and(DB::table($prefix.'customers')->count())->toBe(1)
        ->and(DB::table('permissions')->where('name', 'view organisations')->exists())->toBeTrue();
});

test('v2 upgrade stops when a stranded v2 column has values', function () {
    seedV1Data();
    strandV1Data();

    $prefix = config('laravel-crm.db_table_prefix');
    DB::table($prefix.'leads')->where('id', 1)->update(['customer_id' => 1]);

    $this->artisan('laravelcrm:v2')
        ->expectsConfirmation('Have you taken a backup and do you want to continue?', 'yes')
        ->expectsOutputToContain("Both {$prefix}leads.client_id (2 non-null) and {$prefix}leads.customer_id (1 non-null) exist.")
        ->expectsOutputToContain('Nothing has been changed')
        ->assertExitCode(1);

    expect(Schema::hasTable($prefix.'clients'))->toBeTrue()
        ->and(Schema::hasTable($prefix.'customers'))->toBeTrue()
        ->and(Schema::hasColumn($prefix.'leads', 'client_id'))->toBeTrue()
        ->and(DB::table('permissions')->where('name', 'view organisations')->exists())->toBeTrue();
});
