<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('external_id');
            $table->morphs('contactable');
            $table->morphs('entityable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->foreign('user_created_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->foreign('user_updated_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->foreign('user_deleted_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->foreign('user_restored_id')->references('id')->on('users');
            $table->softDeletes();
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
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'contacts');
    }
}
