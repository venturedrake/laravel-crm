<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmMonitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'monitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('monitor_id')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('https');
            $table->string('url', 1024);
            $table->string('host')->nullable();
            $table->string('method', 16)->default('GET');
            $table->json('headers')->nullable();
            $table->text('body')->nullable();
            $table->unsignedInteger('expected_status_code')->default(200);
            $table->unsignedInteger('interval')->default(5);
            $table->unsignedInteger('timeout')->default(30);
            $table->boolean('is_active')->default(true);
            $table->boolean('uptime_enabled')->default(true);
            $table->boolean('ssl_enabled')->default(false);
            $table->unsignedInteger('perf_threshold_ms')->nullable();
            $table->unsignedInteger('downtime_minutes_before_alert')->nullable();
            $table->string('last_status')->nullable();
            $table->unsignedInteger('last_response_time')->nullable();
            $table->unsignedInteger('last_status_code')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->timestamp('down_since_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('ssl_last_checked_at')->nullable();
            $table->string('ssl_status')->nullable();
            $table->string('ssl_issuer')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('ssl_notified_at')->nullable();
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

            $table->index(['team_id', 'last_checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'monitors');
    }
}
