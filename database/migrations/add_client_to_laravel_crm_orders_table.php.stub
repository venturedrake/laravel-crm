<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientToLaravelCrmOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\Client::class)->after('quote_id')->nullable();
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
             $table->dropColumn('client_id');
        });
    }
}
