<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrefixToLaravelCrmDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
             $table->string('delivery_id')->after('order_id')->nullable();
             $table->string('prefix')->after('delivery_id')->nullable();
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
        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_id',
                'prefix',
                'number'
            ]);   
        });
    }
}
