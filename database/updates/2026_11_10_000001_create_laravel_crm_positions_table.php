<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A position is one job a person holds at an organisation. `end_date` is
     * null while the position is current. `title` is deliberately not an
     * encryptable field: it has to be searchable in SQL.
     *
     * @return void
     */
    public function up()
    {
        $prefix = config('laravel-crm.db_table_prefix');

        if (Schema::hasTable($prefix.'positions')) {
            return;
        }

        Schema::create($prefix.'positions', function (Blueprint $table) use ($prefix) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('person_id')->index();
            $table->foreign('person_id')->references('id')->on($prefix.'people');
            $table->unsignedBigInteger('organization_id')->index();
            $table->foreign('organization_id')->references('id')->on($prefix.'organizations');
            $table->string('title')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->foreign('user_created_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->foreign('user_updated_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->foreign('user_deleted_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->foreign('user_restored_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'positions');
    }
};
