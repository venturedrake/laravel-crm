<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToLaravelCrmOrganisationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->string('vat_number')->nullable();
            $table->string('domain')->nullable();
            $table->string('website_url')->nullable();
            $table->smallInteger('year_founded')->nullable();     
            $table->unsignedBigInteger('timezone_id')->index()->nullable();
            $table->integer('annual_revenue')->nullable();
            $table->bigInteger('total_money_raised')->nullable();
            $table->smallInteger('number_of_employees')->nullable(); 
            $table->string('linkedin')->nullable(); 
            $table->string('facebook')->nullable(); 
            $table->string('twitter')->nullable();   
            $table->string('instagram')->nullable(); 
            $table->string('youtube')->nullable(); 
            $table->string('pinterest')->nullable(); 
            $table->string('tiktok')->nullable(); 
            $table->string('google')->nullable(); 
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->dropColumn([
                'vat_number',
                'domain',
                'website_url',
                'year_founded',
                'timezone_id',
                'annual_revenue',
                'total_money_raised',
                'number_of_employees',
                'linkedin',
                'facebook',
                'twitter',
                'instagram',
                'youtube',
                'pinterest',
                'tiktok',
                'google'    
            ]);
        });
    }
}
