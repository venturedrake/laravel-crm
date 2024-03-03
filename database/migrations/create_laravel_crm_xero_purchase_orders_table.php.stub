<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmXeroPurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'xero_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\PurchaseOrder::class);   
            $table->string('xero_type');
            $table->string('xero_id');
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->integer('subtotal')->nullable();
            $table->integer('total_tax')->nullable();
            $table->integer('total')->nullable();
            $table->string('status')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('line_amount_types')->nullable();
            $table->string('currency_code', 3)->nullable();   
            $table->datetime('xero_updated_at')->nullable();
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
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'xero_purchase_orders');
    }
}
