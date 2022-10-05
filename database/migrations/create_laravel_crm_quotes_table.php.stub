<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'quotes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('lead_id')->index()->nullable();
            $table->foreign('lead_id')->references('id')->on(config('laravel-crm.db_table_prefix').'leads');
            $table->unsignedBigInteger('deal_id')->index()->nullable();
            $table->foreign('deal_id')->references('id')->on(config('laravel-crm.db_table_prefix').'deals');
            $table->unsignedBigInteger('person_id')->index()->nullable();
            $table->foreign('person_id')->references('id')->on(config('laravel-crm.db_table_prefix').'people');
            $table->unsignedBigInteger('organisation_id')->index()->nullable();
            $table->foreign('organisation_id')->references('id')->on(config('laravel-crm.db_table_prefix').'organisations');                   
            $table->string('title');
            $table->text('description')->nullable();         
            $table->datetime('issue_at')->nullable();
            $table->datetime('expire_at')->nullable();                  
            $table->string("currency", 3)->default("USD");
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('adjustments')->nullable();
            $table->integer('total')->nullable();   
            $table->text('terms')->nullable();                                    
            $table->datetime('accepted_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->foreign('user_created_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->foreign('user_updated_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->foreign('user_deleted_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->foreign('user_restored_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->foreign('user_owner_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->foreign('user_assigned_id')->references('id')->on('users');
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
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'quotes');
    }
}
