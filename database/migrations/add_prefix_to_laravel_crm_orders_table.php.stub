<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrefixToLaravelCrmOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
            $table->string('order_id')->after('reference')->nullable();
            $table->string('prefix')->after('order_id')->nullable();
            $table->integer('number')->nullable()->after('prefix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_id',
                'prefix',
                'number'
            ]);     
        });
    }
}
