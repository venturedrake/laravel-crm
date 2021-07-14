<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGlobalToLaravelCrmSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'settings', function (Blueprint $table) {
            $table->boolean('global')->after('team_id')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'settings', function (Blueprint $table) {
            $table->dropColumn(['global']);
        });
    }
}
