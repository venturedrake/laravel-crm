<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryTypeToLaravelCrmPurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'purchase_orders', function (Blueprint $table) {
            $table->enum('delivery_type', ['deliver', 'pickup'])->after('total')->default('deliver');
        });  
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'purchase_orders', function (Blueprint $table) {
             $table->dropColumn([
                'delivery_type',
             ]);
        });
    }
}
