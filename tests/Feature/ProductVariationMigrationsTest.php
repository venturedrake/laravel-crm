<?php

/*
 * The two update migrations behind product variations, run against tables
 * built the way the published stubs built them. TestSchema already declares
 * the new columns, so nothing else in the suite runs these.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function productVariationsMigration()
{
    return require __DIR__.'/../../database/updates/2026_10_22_000001_add_code_and_audit_columns_to_laravel_crm_product_variations_table.php';
}

function purchaseOrderLinesMigration()
{
    return require __DIR__.'/../../database/updates/2026_10_22_000002_add_comments_and_order_product_id_to_laravel_crm_purchase_order_lines_table.php';
}

/**
 * crm_product_variations as create_laravel_crm_product_variations_table.php.stub
 * leaves it, with a row already in it.
 */
function seedLegacyProductVariationsTable(): string
{
    $name = config('laravel-crm.db_table_prefix').'product_variations';

    Schema::dropIfExists($name);

    Schema::create($name, function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('external_id');
        $table->unsignedBigInteger('team_id')->index()->nullable();
        $table->unsignedBigInteger('product_id')->index();
        $table->string('name');
        $table->text('description')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    DB::table($name)->insert(['external_id' => 'legacy', 'product_id' => 1, 'name' => 'Legacy']);

    return $name;
}

function seedLegacyPurchaseOrderLinesTable(): string
{
    $name = config('laravel-crm.db_table_prefix').'purchase_order_lines';

    Schema::dropIfExists($name);

    Schema::create($name, function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('external_id');
        $table->unsignedBigInteger('purchase_order_id')->index();
        $table->unsignedBigInteger('product_id')->index()->nullable();
        $table->unsignedBigInteger('product_variation_id')->index()->nullable();
        $table->text('description')->nullable();
        $table->integer('price')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    DB::table($name)->insert(['external_id' => 'legacy', 'purchase_order_id' => 1, 'price' => 100]);

    return $name;
}

$variationColumns = [
    'code', 'barcode', 'order', 'active',
    'user_created_id', 'user_updated_id', 'user_deleted_id', 'user_restored_id',
];

test('the variations migration adds its columns and keeps existing rows', function () use ($variationColumns) {
    $name = seedLegacyProductVariationsTable();

    productVariationsMigration()->up();

    expect(Schema::hasColumns($name, $variationColumns))->toBeTrue();
    expect(Schema::hasIndex($name, $name.'_code_index'))->toBeTrue();

    $row = DB::table($name)->first();
    expect($row->name)->toBe('Legacy');
    expect((bool) $row->active)->toBeTrue();
});

test('the variations migration is idempotent', function () use ($variationColumns) {
    $name = seedLegacyProductVariationsTable();

    productVariationsMigration()->up();
    productVariationsMigration()->up();

    expect(Schema::hasColumns($name, $variationColumns))->toBeTrue();
});

test('the variations migration is a no-op when the columns already exist', function () {
    // TestSchema builds the table with every column (and the code index) in place.
    $name = config('laravel-crm.db_table_prefix').'product_variations';
    $before = Schema::getColumnListing($name);

    productVariationsMigration()->up();

    expect(Schema::getColumnListing($name))->toBe($before);
});

test('the variations migration rolls back what it added', function () use ($variationColumns) {
    $name = seedLegacyProductVariationsTable();

    productVariationsMigration()->up();
    productVariationsMigration()->down();

    foreach ($variationColumns as $column) {
        expect(Schema::hasColumn($name, $column))->toBeFalse("$column still on $name");
    }

    expect(Schema::hasColumns($name, ['name', 'description']))->toBeTrue();
    expect(DB::table($name)->count())->toBe(1);

    // And forward again.
    productVariationsMigration()->up();
    expect(Schema::hasColumns($name, $variationColumns))->toBeTrue();
});

test('the purchase order lines migration adds comments and order_product_id', function () {
    $name = seedLegacyPurchaseOrderLinesTable();

    purchaseOrderLinesMigration()->up();
    purchaseOrderLinesMigration()->up();

    expect(Schema::hasColumns($name, ['comments', 'order_product_id']))->toBeTrue();
    expect(DB::table($name)->value('price'))->toBe(100);

    DB::table($name)->insert(['external_id' => 'new', 'purchase_order_id' => 1, 'comments' => 'Rush', 'order_product_id' => 7]);
    expect(DB::table($name)->where('external_id', 'new')->value('order_product_id'))->toBe(7);
});

test('the purchase order lines migration is a no-op when the columns already exist', function () {
    $name = config('laravel-crm.db_table_prefix').'purchase_order_lines';
    $before = Schema::getColumnListing($name);

    purchaseOrderLinesMigration()->up();

    expect(Schema::getColumnListing($name))->toBe($before);
});

test('the purchase order lines migration rolls back', function () {
    $name = seedLegacyPurchaseOrderLinesTable();

    purchaseOrderLinesMigration()->up();
    purchaseOrderLinesMigration()->down();

    expect(Schema::hasColumn($name, 'comments'))->toBeFalse();
    expect(Schema::hasColumn($name, 'order_product_id'))->toBeFalse();
    expect(DB::table($name)->count())->toBe(1);
});

test('both migrations skip a missing table', function () {
    Schema::dropIfExists(config('laravel-crm.db_table_prefix').'product_variations');
    Schema::dropIfExists(config('laravel-crm.db_table_prefix').'purchase_order_lines');

    productVariationsMigration()->up();
    purchaseOrderLinesMigration()->up();
    productVariationsMigration()->down();
    purchaseOrderLinesMigration()->down();

    expect(Schema::hasTable(config('laravel-crm.db_table_prefix').'product_variations'))->toBeFalse();
});
