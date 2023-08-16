<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmTaxRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'tax_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('rate');
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'products', function (Blueprint $table) {
            $table->foreignIdFor(\VentureDrake\LaravelCrm\Models\TaxRate::class)->nullable();
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
                'tax_rate_id',
            ]);
        });
                    
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'tax_rates');
    }
}
