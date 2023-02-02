<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTeamIdToLaravelCrmUsageRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'usage_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'usage_requests', function (Blueprint $table) {
            $table->dropColumn(['team_id']);
        });
    }
}
