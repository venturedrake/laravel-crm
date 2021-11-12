<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmOrganisationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'organisation_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->unsignedBigInteger('organisation_type_id')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
                $table->dropColumn(['organisation_type_id']);
        });
  
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'organisation_types');
    }
}
