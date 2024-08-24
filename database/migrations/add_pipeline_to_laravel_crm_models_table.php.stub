<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deals', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'quotes', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'purchase_orders', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Pipeline::class)->nullable()->after('team_id');
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PipelineStage::class)->nullable()->after('pipeline_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deals', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'quotes', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_id',
                'pipeline_stage_id'
            ]);
        });
    }
};
