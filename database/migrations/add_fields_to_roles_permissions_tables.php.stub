<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToRolesPermissionsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('roles', 'description'))
        {
            Schema::table('roles', function (Blueprint $table) {
                $table->string("description")->after('guard_name')->nullable();
            });
        }  
        if (! Schema::hasColumn('roles', 'crm_role'))
        {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('crm_role')->after('description')->default(0);
            });
        } 
                
        if (! Schema::hasColumn('permissions', 'description'))
        {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string("description")->after('guard_name')->nullable();
            });
        }  
        if (! Schema::hasColumn('permissions', 'crm_permission'))
        {
            Schema::table('permissions', function (Blueprint $table) {
                $table->boolean('crm_permission')->after('description')->default(0);
            });
        } 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
