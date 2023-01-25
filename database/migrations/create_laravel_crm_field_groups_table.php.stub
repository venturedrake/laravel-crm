<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmFieldGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'field_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name');
            $table->string('handle')->nullable();
            $table->timestamps();
            $table->softDeletes();      
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
             $table->unsignedBigInteger('field_group_id')->index()->after('team_id');
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
            $table->dropColumn(['field_group_id']);
        });
    
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'field_groups');
    }
}
