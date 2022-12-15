<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('order_id')->index()->nullable();
            $table->foreign('order_id')->references('id')->on(config('laravel-crm.db_table_prefix').'orders');
            $table->unsignedBigInteger('person_id')->index()->nullable();
            $table->foreign('person_id')->references('id')->on(config('laravel-crm.db_table_prefix').'people');
            $table->unsignedBigInteger('organisation_id')->index()->nullable();
            $table->foreign('organisation_id')->references('id')->on(config('laravel-crm.db_table_prefix').'organisations');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('invoice_id');
            $table->unsignedBigInteger('invoice_number');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string("currency", 3)->default("USD");
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('adjustments')->nullable();
            $table->integer('total')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('sent')->default(false);
            $table->integer('amount_due')->nullable();
            $table->integer('amount_paid')->nullable();
            $table->datetime('fully_paid_at')->nullable();
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
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'invoices');
    }
}
