<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->bigInteger('person_id')->index()->nullable();
            $table->foreign('person_id')->references('id')->on('people');
            $table->bigInteger('organisation_id')->index()->nullable();
            $table->foreign('organisation_id')->references('id')->on('organisations');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('amount')->default(0);
            $table->string("currency", 3)->default("USD");
            $table->smallInteger('lead_status_id')->index()->nullable();
            $table->boolean("qualified")->default(false);
            $table->datetime('expected_close')->nullable();
            $table->foreign('lead_status_id')->references('id')->on('lead_statuses');
            $table->integer('user_created_id')->unsigned();
            $table->foreign('user_created_id')->references('id')->on('users');
            $table->integer('user_updated_id')->unsigned();
            $table->foreign('user_updated_id')->references('id')->on('users');
            $table->integer('user_deleted_id')->unsigned();
            $table->foreign('user_deleted_id')->references('id')->on('users');
            $table->integer('user_restored_id')->unsigned();
            $table->foreign('user_restored_id')->references('id')->on('users');
            $table->integer('user_assigned_id')->unsigned();
            $table->foreign('user_assigned_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create(config('laravel-crm.db_table_prefix').'lead_statuses', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('external_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->smallInteger('order')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        \DB::statement('UPDATE '.config('laravel-crm.db_table_prefix').'lead_statuses SET order = id');
        Schema::create(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->timestamps();
        });
        Schema::create(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->timestamps();
        });
        Schema::create(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->timestamps();
        });
        Schema::create(config('laravel-crm.db_table_prefix').'addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->timestamps();
        });
        Schema::create(config('laravel-crm.db_table_prefix').'activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'leads');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'lead_statuses');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'people');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'organisations');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'emails');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'phones');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'addresses');
    }
}
