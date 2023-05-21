<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteProductIdToLaravelCrmOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'order_products', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\QuoteProduct::class)->nullable()->after('comments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'order_products', function (Blueprint $table) {
            $table->dropColumn([
                'quote_product_id',
            ]);
        });
    }
}
