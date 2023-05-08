<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountCodesToLaravelCrmProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'products', function (Blueprint $table) {
            $table->string('purchase_account')->after('code')->nullable();
            $table->string('sales_account')->after('code')->nullable();
        });  
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'products', function (Blueprint $table) {
             $table->dropColumn([
                'purchase_account',
                'sales_account'
             ]);
        });
    }
}
