<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmMonitorChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'monitor_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('monitor_id');
            $table->foreign('monitor_id')
                ->references('id')
                ->on(config('laravel-crm.db_table_prefix').'monitors')
                ->onDelete('cascade');
            $table->string('type')->default('http');
            $table->string('status');
            $table->unsignedInteger('response_time')->nullable();
            $table->unsignedInteger('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['monitor_id', 'type', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'monitor_checks');
    }
}
