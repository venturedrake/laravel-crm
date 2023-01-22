<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmUsageRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'usage_requests', function (Blueprint $table) {
            $table->id();
            $table->string('host'); 
            $table->string('path');
            $table->string('url');
            $table->string('method');
            $table->string('route');
            $table->string('visitor')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('response_time')->nullable();
            $table->string('day')->nullable();
            $table->tinyInteger('hour')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'usage_requests');
    }
}
