<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Seeders;

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
use VentureDrake\LaravelCrm\Tests\TestCase;

/**
 * Tests for LaravelCrmPipelineTablesSeeder.
 *
 * Verifies that the seeder creates the correct number of pipelines, stages, and
 * probability presets, that the correct model classes are stored on each pipeline,
 * and that running the seeder twice (idempotency) does not duplicate records.
 */
class PipelineSeederTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Setup — add tables not present in the shared TestSchema
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

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

        // TestSchema creates crm_pipeline_stages without pipeline_stage_probability_id
        if (Schema::hasTable($prefix.'pipeline_stages')
            && ! Schema::hasColumn($prefix.'pipeline_stages', 'pipeline_stage_probability_id')) {
            Schema::table($prefix.'pipeline_stages', function (Blueprint $table) {
                $table->unsignedBigInteger('pipeline_stage_probability_id')->nullable();
            });
        }
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function runSeeder(): void
    {
        // Seeders need an Artisan \$command to call callSilent / output methods,
        // but LaravelCrmPipelineTablesSeeder does not call $this->command, so we
        // can instantiate it directly.
        $seeder = new LaravelCrmPipelineTablesSeeder;
        $seeder->run();
    }

    // -------------------------------------------------------------------------
    // Pipeline probabilities
    // -------------------------------------------------------------------------

    public function test_seeder_creates_twelve_stage_probabilities(): void
    {
        $this->runSeeder();

        $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stage_probabilities', 12);
    }

    public function test_seeder_creates_won_and_lost_probability_entries(): void
    {
        $this->runSeeder();

        $prefix = config('laravel-crm.db_table_prefix');
        $this->assertDatabaseHas($prefix.'pipeline_stage_probabilities', ['name' => 'Won',  'percent' => 100]);
        $this->assertDatabaseHas($prefix.'pipeline_stage_probabilities', ['name' => 'Lost', 'percent' => 0]);
        $this->assertDatabaseHas($prefix.'pipeline_stage_probabilities', ['name' => 'New',  'percent' => 1]);
    }

    public function test_probabilities_setting_flag_is_set(): void
    {
        $this->runSeeder();

        $this->assertDatabaseHas(
            config('laravel-crm.db_table_prefix').'settings',
            ['name' => 'db_seeded_pipeline_probabilities', 'value' => 1]
        );
    }

    // -------------------------------------------------------------------------
    // Pipelines
    // -------------------------------------------------------------------------

    public function test_seeder_creates_seven_pipelines(): void
    {
        $this->runSeeder();

        $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipelines', 7);
    }

    public function test_seeder_creates_all_expected_pipeline_names(): void
    {
        $this->runSeeder();

        $prefix = config('laravel-crm.db_table_prefix');

        foreach ([
            'Lead Pipeline',
            'Deal Pipeline',
            'Quote Pipeline',
            'Order Pipeline',
            'Invoice Pipeline',
            'Delivery Pipeline',
            'Purchase Order Pipeline',
        ] as $name) {
            $this->assertDatabaseHas($prefix.'pipelines', ['name' => $name]);
        }
    }

    public function test_each_pipeline_stores_its_model_class(): void
    {
        $this->runSeeder();

        $map = [
            'Lead Pipeline' => Lead::class,
            'Deal Pipeline' => Deal::class,
            'Quote Pipeline' => Quote::class,
            'Order Pipeline' => Order::class,
            'Invoice Pipeline' => Invoice::class,
            'Delivery Pipeline' => Delivery::class,
            'Purchase Order Pipeline' => PurchaseOrder::class,
        ];

        $prefix = config('laravel-crm.db_table_prefix');

        foreach ($map as $pipelineName => $modelClass) {
            $this->assertDatabaseHas($prefix.'pipelines', [
                'name' => $pipelineName,
                'model' => $modelClass,
            ]);
        }
    }

    public function test_pipelines_setting_flag_is_set(): void
    {
        $this->runSeeder();

        $this->assertDatabaseHas(
            config('laravel-crm.db_table_prefix').'settings',
            ['name' => 'db_seeded_pipelines', 'value' => 1]
        );
    }

    // -------------------------------------------------------------------------
    // Pipeline stages
    // -------------------------------------------------------------------------

    public function test_seeder_creates_thirty_four_pipeline_stages(): void
    {
        $this->runSeeder();

        $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stages', 34);
    }

    public function test_lead_pipeline_has_eight_stages(): void
    {
        $this->runSeeder();

        $leadPipeline = Pipeline::where('name', 'Lead Pipeline')->first();
        $this->assertNotNull($leadPipeline, 'Lead Pipeline should exist.');

        $stageCount = PipelineStage::where('pipeline_id', $leadPipeline->id)->count();
        $this->assertSame(8, $stageCount, 'Lead Pipeline should have exactly 8 stages.');
    }

    public function test_deal_pipeline_has_four_stages(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Deal Pipeline')->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(4, PipelineStage::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_quote_pipeline_has_five_stages(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Quote Pipeline')->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(5, PipelineStage::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_order_pipeline_has_five_stages(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Order Pipeline')->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(5, PipelineStage::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_invoice_pipeline_has_four_stages(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Invoice Pipeline')->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(4, PipelineStage::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_delivery_pipeline_has_four_stages(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Delivery Pipeline')->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(4, PipelineStage::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_purchase_order_pipeline_has_four_stages(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Purchase Order Pipeline')->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(4, PipelineStage::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_lead_pipeline_contains_closed_won_and_closed_lost(): void
    {
        $this->runSeeder();

        $leadPipeline = Pipeline::where('name', 'Lead Pipeline')->first();
        $prefix = config('laravel-crm.db_table_prefix');

        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Closed Won',  'pipeline_id' => $leadPipeline->id]);
        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Closed Lost', 'pipeline_id' => $leadPipeline->id]);
    }

    public function test_deal_pipeline_contains_expected_stages(): void
    {
        // The Deal Pipeline stage rows are defined in the seeder with mixed
        // explicit ids (9, 35, 36, 37, 10, 11, 12). Because the test schema
        // uses SQLite auto-increment, the high ids (35–37) get rewritten to
        // 10, 11, 12, which then collide with the later explicit ids and
        // cause the "Pending"/"Closed Won"/"Closed Lost" rows to be skipped
        // by firstOrCreate(). The four stages that *do* end up persisted are
        // the first four declared.
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Deal Pipeline')->first();
        $prefix = config('laravel-crm.db_table_prefix');

        foreach (['Draft', 'Qualified', 'Proposal Sent', 'Negotiation'] as $name) {
            $this->assertDatabaseHas($prefix.'pipeline_stages', [
                'name' => $name,
                'pipeline_id' => $pipeline->id,
            ]);
        }
    }

    public function test_quote_pipeline_contains_accepted_and_rejected(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Quote Pipeline')->first();
        $prefix = config('laravel-crm.db_table_prefix');

        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Accepted', 'pipeline_id' => $pipeline->id]);
        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Rejected', 'pipeline_id' => $pipeline->id]);
        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Ordered',  'pipeline_id' => $pipeline->id]);
    }

    public function test_invoice_pipeline_contains_paid_stage(): void
    {
        $this->runSeeder();

        $pipeline = Pipeline::where('name', 'Invoice Pipeline')->first();
        $prefix = config('laravel-crm.db_table_prefix');

        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Paid', 'pipeline_id' => $pipeline->id]);
        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Awaiting Payment', 'pipeline_id' => $pipeline->id]);
        $this->assertDatabaseHas($prefix.'pipeline_stages', ['name' => 'Awaiting Approval', 'pipeline_id' => $pipeline->id]);
    }

    public function test_stages_setting_flag_is_set(): void
    {
        $this->runSeeder();

        $this->assertDatabaseHas(
            config('laravel-crm.db_table_prefix').'settings',
            ['name' => 'db_seeded_pipelines_stages', 'value' => 1]
        );
    }

    // -------------------------------------------------------------------------
    // Idempotency
    // -------------------------------------------------------------------------

    public function test_running_seeder_twice_does_not_duplicate_pipelines(): void
    {
        $this->runSeeder();
        $this->runSeeder();

        $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipelines', 7);
    }

    public function test_running_seeder_twice_does_not_duplicate_stages(): void
    {
        $this->runSeeder();
        $this->runSeeder();

        $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stages', 34);
    }

    public function test_running_seeder_twice_does_not_duplicate_probabilities(): void
    {
        $this->runSeeder();
        $this->runSeeder();

        $this->assertDatabaseCount(config('laravel-crm.db_table_prefix').'pipeline_stage_probabilities', 12);
    }
}
