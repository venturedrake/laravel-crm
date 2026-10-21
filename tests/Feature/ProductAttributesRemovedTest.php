<?php

/*
 * Product Attributes was an unfinished v1 stub: routes, a controller, a policy and
 * four seeded permissions, but no migration and no views, so every route errored
 * (#53). It has been removed. This file keeps it removed, and pins the
 * database/updates migration that clears the orphaned permissions from existing
 * installs.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

const PRODUCT_ATTRIBUTE_PERMISSIONS = [
    'create crm product attributes',
    'view crm product attributes',
    'edit crm product attributes',
    'delete crm product attributes',
];

/**
 * The slice of Spatie's permission tables the migration touches, created here
 * because TestSchema does not ship them.
 */
function createPermissionTables(): void
{
    Schema::create(config('permission.table_names.permissions'), function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name');
        $table->string('guard_name');
        $table->boolean('crm_permission')->default(0);
        $table->timestamps();
    });

    Schema::create(config('permission.table_names.role_has_permissions'), function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
    });

    Schema::create(config('permission.table_names.model_has_permissions'), function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
    });
}

function insertPermission(string $name): int
{
    return DB::table(config('permission.table_names.permissions'))->insertGetId([
        'name' => $name,
        'guard_name' => 'web',
        'crm_permission' => 1,
    ]);
}

function productAttributePermissionsMigration(): object
{
    return require __DIR__.'/../../database/updates/2026_10_21_143327_remove_product_attribute_permissions_from_laravel_crm.php';
}

test('no product-attributes route is registered', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter(fn ($name) => str_starts_with($name ?? '', 'laravel-crm.product-attributes.'))
        ->values()
        ->all();

    expect($routes)->toBe([]);
});

test('the product attribute classes are gone', function () {
    expect(class_exists('VentureDrake\LaravelCrm\Models\ProductAttribute'))->toBeFalse()
        ->and(class_exists('VentureDrake\LaravelCrm\Policies\ProductAttributePolicy'))->toBeFalse()
        ->and(class_exists('VentureDrake\LaravelCrm\Http\Controllers\ProductAttributeController'))->toBeFalse();
});

test('the seeder no longer creates the product attribute permissions', function () {
    $seeder = file_get_contents(__DIR__.'/../../database/seeders/LaravelCrmTablesSeeder.php');

    expect($seeder)->not->toContain('crm product attributes');
});

test('the migration removes the product attribute permissions and their assignments', function () {
    createPermissionTables();

    $kept = insertPermission('view crm product categories');
    $removed = array_map('insertPermission', PRODUCT_ATTRIBUTE_PERMISSIONS);

    foreach ([$kept, ...$removed] as $id) {
        DB::table(config('permission.table_names.role_has_permissions'))->insert(['permission_id' => $id, 'role_id' => 1]);
        DB::table(config('permission.table_names.model_has_permissions'))->insert(['permission_id' => $id, 'model_type' => 'User', 'model_id' => 1]);
    }

    productAttributePermissionsMigration()->up();

    expect(DB::table(config('permission.table_names.permissions'))->pluck('name')->all())
        ->toBe(['view crm product categories'])
        ->and(DB::table(config('permission.table_names.role_has_permissions'))->pluck('permission_id')->all())
        ->toBe([$kept])
        ->and(DB::table(config('permission.table_names.model_has_permissions'))->pluck('permission_id')->all())
        ->toBe([$kept]);
});

test('the migration is a no-op when the permissions are already gone', function () {
    createPermissionTables();
    insertPermission('view crm product categories');

    productAttributePermissionsMigration()->up();
    productAttributePermissionsMigration()->up();

    expect(DB::table(config('permission.table_names.permissions'))->pluck('name')->all())
        ->toBe(['view crm product categories']);
});

test('the migration is a no-op when the permissions table does not exist', function () {
    expect(Schema::hasTable(config('permission.table_names.permissions')))->toBeFalse();

    productAttributePermissionsMigration()->up();
    productAttributePermissionsMigration()->down();

    expect(Schema::hasTable(config('permission.table_names.permissions')))->toBeFalse();
});

test('down() recreates the product attribute permissions as crm permissions', function () {
    createPermissionTables();
    array_map('insertPermission', PRODUCT_ATTRIBUTE_PERMISSIONS);

    $migration = productAttributePermissionsMigration();
    $migration->up();
    $migration->down();

    $restored = DB::table(config('permission.table_names.permissions'))
        ->whereIn('name', PRODUCT_ATTRIBUTE_PERMISSIONS)
        ->get();

    expect($restored->pluck('name')->sort()->values()->all())
        ->toBe(collect(PRODUCT_ATTRIBUTE_PERMISSIONS)->sort()->values()->all())
        ->and($restored->pluck('crm_permission')->map(fn ($flag) => (int) $flag)->unique()->all())
        ->toBe([1]);
});
