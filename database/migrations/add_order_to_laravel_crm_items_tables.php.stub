<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'quote_products', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('comments');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deal_products', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('currency');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'order_products', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('comments');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'invoice_lines', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('comments');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'delivery_products', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('quantity');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'purchase_order_lines', function (Blueprint $table) {
            $table->integer('order')->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'quote_products', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'deal_products', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'order_products', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'invoice_lines', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'delivery_products', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
