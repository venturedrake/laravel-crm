<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmPipelineTablesSeeder;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;

/**
 * Tests for LaravelCrmPipelineTablesSeeder.
 */
beforeEach(function () {
    $prefix = config('laravel-crm.db_table_prefix');

    if (! Schema::hasTable($prefix.'pipeline_stage_probabilities')) {
        Schema::create($prefix.'pipeline_stage_probabilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->integer('percent')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (Schema::hasTable($prefix.'pipeline_stages')
        && ! Schema::hasColumn($prefix.'pipeline_stages', 'pipeline_stage_probability_id')) {
        Schema::table($prefix.'pipeline_stages', function (Blueprint $table) {
            $table->unsignedBigInteger('pipeline_stage_probability_id')->nullable();
        });
    }
});

function runSeeder(): void
{
    $seeder = new LaravelCrmPipelineTablesSeeder;
    $seeder->run();
}

// Pipeline probabilities

test('seeder creates twelve stage probabilities', function () {
    runSeeder();

    $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stage_probabilities', 12);
});

test('seeder creates won and lost probability entries', function () {
    runSeeder();

    $prefix = config('laravel-crm.db_table_prefix');
    $this->assertDatabaseHas($prefix.'pipeline_stage_probabilities', ['name' => 'Won', 'percent' => 100]);
    $this->assertDatabaseHas($prefix.'pipeline_stage_probabilities', ['name' => 'Lost', 'percent' => 0]);
    $this->assertDatabaseHas($prefix.'pipeline_stage_probabilities', ['name' => 'New', 'percent' => 1]);
});

test('probabilities setting flag is set', function () {
    runSeeder();

    $this->assertDatabaseHas(
        config('laravel-crm.db_table_prefix').'settings',
        ['name' => 'db_seeded_pipeline_probabilities', 'value' => 1]
    );
});

// Pipelines

test('seeder creates seven pipelines', function () {
    runSeeder();

    $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipelines', 7);
});

test('seeder creates all expected pipeline names', function () {
    runSeeder();

    $prefix = config('laravel-crm.db_table_prefix');
    foreach (['Lead Pipeline', 'Deal Pipeline', 'Quote Pipeline', 'Order Pipeline', 'Invoice Pipeline', 'Delivery Pipeline', 'Purchase Order Pipeline'] as $name) {
        $this->assertDatabaseHas($prefix.'pipelines', ['name' => $name]);
    }
});

test('each pipeline stores its model class', function () {
    runSeeder();

    $map = [
        'Lead Pipeline' => Lead::class, 'Deal Pipeline' => Deal::class,
        'Quote Pipeline' => Quote::class, 'Order Pipeline' => Order::class,
        'Invoice Pipeline' => Invoice::class, 'Delivery Pipeline' => Delivery::class,
        'Purchase Order Pipeline' => PurchaseOrder::class,
    ];

    $prefix = config('laravel-crm.db_table_prefix');
    foreach ($map as $pipelineName => $modelClass) {
        $this->assertDatabaseHas($prefix.'pipelines', ['name' => $pipelineName, 'model' => $modelClass]);
    }
});

test('pipelines setting flag is set', function () {
    runSeeder();

    $this->assertDatabaseHas(config('laravel-crm.db_table_prefix').'settings', ['name' => 'db_seeded_pipelines', 'value' => 1]);
});

// Pipeline stages

test('seeder creates thirty four pipeline stages', function () {
    runSeeder();

    $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stages', 34);
});

test('lead pipeline has eight stages', function () {
    runSeeder();

    $leadPipeline = Pipeline::where('name', 'Lead Pipeline')->first();
    expect($leadPipeline)->not->toBeNull('Lead Pipeline should exist.');
    expect(PipelineStage::where('pipeline_id', $leadPipeline->id)->count())->toBe(8);
});

test('deal pipeline has four stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Deal Pipeline')->first();
    expect($pipeline)->not->toBeNull();
    expect(PipelineStage::where('pipeline_id', $pipeline->id)->count())->toBe(4);
});

test('quote pipeline has five stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Quote Pipeline')->first();
    expect($pipeline)->not->toBeNull();
    expect(PipelineStage::where('pipeline_id', $pipeline->id)->count())->toBe(5);
});

test('order pipeline has five stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Order Pipeline')->first();
    expect($pipeline)->not->toBeNull();
    expect(PipelineStage::where('pipeline_id', $pipeline->id)->count())->toBe(5);
});

test('invoice pipeline has four stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Invoice Pipeline')->first();
    expect($pipeline)->not->toBeNull();
    expect(PipelineStage::where('pipeline_id', $pipeline->id)->count())->toBe(4);
});

test('delivery pipeline has four stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Delivery Pipeline')->first();
    expect($pipeline)->not->toBeNull();
    expect(PipelineStage::where('pipeline_id', $pipeline->id)->count())->toBe(4);
});

test('purchase order pipeline has four stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Purchase Order Pipeline')->first();
    expect($pipeline)->not->toBeNull();
    expect(PipelineStage::where('pipeline_id', $pipeline->id)->count())->toBe(4);
});

test('lead pipeline contains closed won and closed lost', function () {
    runSeeder();

    $leadPipeline = Pipeline::where('name', 'Lead Pipeline')->first();
    $prefix = config('laravel-crm.db_table_prefix');

    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Closed Won', 'pipeline_id' => $leadPipeline->id]);
    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Closed Lost', 'pipeline_id' => $leadPipeline->id]);
});

test('deal pipeline contains expected stages', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Deal Pipeline')->first();
    $prefix = config('laravel-crm.db_table_prefix');

    foreach (['Draft', 'Qualified', 'Proposal Sent', 'Negotiation'] as $name) {
        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => $name, 'pipeline_id' => $pipeline->id]);
    }
});

test('quote pipeline contains accepted and rejected', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Quote Pipeline')->first();
    $prefix = config('laravel-crm.db_table_prefix');

    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Accepted', 'pipeline_id' => $pipeline->id]);
    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Rejected', 'pipeline_id' => $pipeline->id]);
    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Ordered', 'pipeline_id' => $pipeline->id]);
});

test('invoice pipeline contains paid stage', function () {
    runSeeder();

    $pipeline = Pipeline::where('name', 'Invoice Pipeline')->first();
    $prefix = config('laravel-crm.db_table_prefix');

    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Paid', 'pipeline_id' => $pipeline->id]);
    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Awaiting Payment', 'pipeline_id' => $pipeline->id]);
    $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Awaiting Approval', 'pipeline_id' => $pipeline->id]);
});

test('stages setting flag is set', function () {
    runSeeder();

    $this->assertDatabaseHas(config('laravel-crm.db_table_prefix').'settings', ['name' => 'db_seeded_pipelines_stages', 'value' => 1]);
});

// Idempotency

test('running seeder twice does not duplicate pipelines', function () {
    runSeeder();
    runSeeder();

    $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipelines', 7);
});

test('running seeder twice does not duplicate stages', function () {
    runSeeder();
    runSeeder();

    $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stages', 34);
});

test('running seeder twice does not duplicate probabilities', function () {
    runSeeder();
    runSeeder();

    $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stage_probabilities', 12);
});
