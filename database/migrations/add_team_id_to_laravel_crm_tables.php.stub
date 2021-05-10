<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdToLaravelCrmTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'settings', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'lead_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'lead_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deals', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'field_values', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'labels', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table('crm_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'activities', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });

        Schema::table(config('laravel-crm.db_table_prefix').'notes', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('external_id')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'settings', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'addresses', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'lead_statuses', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'lead_sources', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'deals', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'field_values', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'labels', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table('crm_teams', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'activities', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'notes', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });
    }
}
