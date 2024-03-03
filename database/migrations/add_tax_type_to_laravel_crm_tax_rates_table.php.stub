<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxTypeToLaravelCrmTaxRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'tax_rates', function (Blueprint $table) {
            $table->string('tax_type')->after('default')->nullable();
        });  
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'tax_rates', function (Blueprint $table) {
             $table->dropColumn([
                'tax_type',
             ]);
        });
    }
}
