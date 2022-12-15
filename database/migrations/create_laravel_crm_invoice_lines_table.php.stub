<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmInvoiceLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'invoice_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->foreign('invoice_id')->references('id')->on(config('laravel-crm.db_table_prefix').'invoices');
            $table->unsignedBigInteger('product_id')->index()->nullable();
            $table->foreign('product_id')->references('id')->on(config('laravel-crm.db_table_prefix').'products');
            $table->unsignedBigInteger('product_variation_id')->index()->nullable();
            $table->foreign('product_variation_id')->references('id')->on(config('laravel-crm.db_table_prefix').'product_variations');
            $table->text('description')->nullable();
            $table->integer('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string("currency", 3)->default("USD");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'invoice_lines');
    }
}
