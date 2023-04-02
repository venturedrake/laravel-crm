<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToLaravelCrmClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'clients', function (Blueprint $table) {
            $table->string('name')->after('clientable_id')->nullable();
            $table->string('clientable_type')->nullable()->change();
            $table->unsignedBigInteger('clientable_id')->nullable()->change();
        });  
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'clients', function (Blueprint $table) {
            $table->dropColumn(['name']);
            $table->string('clientable_type')->nullable(false)->change();
            $table->unsignedBigInteger('clientable_id')->nullable(false)->change();
        });
    }
}
