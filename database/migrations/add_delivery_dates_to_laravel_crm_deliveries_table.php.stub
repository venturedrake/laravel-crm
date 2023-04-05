<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryDatesToLaravelCrmDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
            $table->datetime('delivery_initiated')->after('order_id')->nullable();
            $table->datetime('delivery_shipped')->after('delivery_initiated')->nullable();
            $table->datetime('delivery_expected')->after('delivery_shipped')->nullable();
            $table->datetime('delivered_on')->after('delivery_expected')->nullable();
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
                'delivery_initiated',
                'delivery_shipped',
                'delivery_expected',
                'delivered_at'
            ]);
        });
    }
}
