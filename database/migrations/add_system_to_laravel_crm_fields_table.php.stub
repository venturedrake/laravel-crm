<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSystemToLaravelCrmFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
            $table->boolean('system')->after('validation')->default(false);
        });  
        
        Schema::table(config('laravel-crm.db_table_prefix').'field_groups', function (Blueprint $table) {
            $table->boolean('system')->after('handle')->default(false);
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
            $table->dropColumn(['system']);
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'field_groups', function (Blueprint $table) {
            $table->dropColumn(['system']);
        });
    }
}
