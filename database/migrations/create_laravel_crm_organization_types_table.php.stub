<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmOrganizationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'organization_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_type_id')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'organizations', function (Blueprint $table) {
                $table->dropColumn(['organization_type_id']);
        });
  
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'organization_types');
    }
}
