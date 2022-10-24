<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmXeroItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'xero_items', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Product::class);
            $table->string('item_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('inventory_tracked')->default(false);
            $table->boolean('is_purchased')->default(false);
            $table->integer('purchase_price')->nullable();
            $table->string('purchase_description')->nullable();
            $table->boolean('is_sold')->default(false);
            $table->integer('sell_price')->nullable();
            $table->string('sell_description')->nullable();
            $table->integer('quantity_on_hand')->nullable();
            $table->dateTime('updated_date')->nullable();
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
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'xero_items');
    }
}
