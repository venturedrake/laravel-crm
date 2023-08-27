<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderProductIdToLaravelCrmInvoiceLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'invoice_lines', function (Blueprint $table) {
              $table->unsignedBigInteger('order_product_id')->index()->nullable()->after('product_variation_id');
              $table->foreign('order_product_id')->references('id')->on(config('laravel-crm.db_table_prefix').'order_products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'invoice_lines', function (Blueprint $table) {
            $table->dropColumn([
                'order_product_id',
            ]);
        });
    }
}
