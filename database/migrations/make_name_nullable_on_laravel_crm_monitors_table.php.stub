<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeNameNullableOnLaravelCrmMonitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'monitors', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'monitors', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
}
