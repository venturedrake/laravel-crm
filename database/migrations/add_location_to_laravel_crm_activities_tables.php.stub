<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationToLaravelCrmActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'calls', function (Blueprint $table) {
            $table->string('location')->nullable()->after('finish_at');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'meetings', function (Blueprint $table) {
            $table->string('location')->nullable()->after('finish_at');
        });

        Schema::table(config('laravel-crm.db_table_prefix').'lunches', function (Blueprint $table) {
            $table->string('location')->nullable()->after('finish_at');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'calls', function (Blueprint $table) {
            $table->dropColumn([
                'location',
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'meetings', function (Blueprint $table) {
            $table->dropColumn([
                'location',
            ]);
        });

        Schema::table(config('laravel-crm.db_table_prefix').'lunches', function (Blueprint $table) {
            $table->dropColumn([
                'location',
            ]);
        });
    }
}
