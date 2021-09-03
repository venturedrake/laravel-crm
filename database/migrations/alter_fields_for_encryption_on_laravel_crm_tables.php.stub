<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFieldsForEncryptionOnLaravelCrmTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('laravel-crm.encrypt_db_fields')) {
            Schema::table(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
                $table->string('title', 500)->nullable()->change();
                $table->string('first_name', 500)->change();
                $table->string('middle_name', 500)->nullable()->change();
                $table->string('last_name', 500)->nullable()->change();
                $table->string('maiden_name', 500)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
                $table->string('name', 1000)->nullable()->change();
            });
            
            Schema::table(config('laravel-crm.db_table_prefix').'addresses', function (Blueprint $table) {
                $table->string('address', 1000)->nullable()->change();
                $table->string('line1', 1000)->change();
                $table->string('line2', 1000)->nullable()->change();
                $table->string('line3', 1000)->nullable()->change();
                $table->string('code', 500)->nullable()->change();
                $table->string('city', 500)->nullable()->change();
                $table->string('state', 500)->nullable()->change();
                $table->string('country', 500)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
                $table->string('address', 500)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
                $table->string('number', 500)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('laravel-crm.encrypt_db_fields')) {
            Schema::table(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
                $table->string('title', 255)->nullable()->change();
                $table->string('first_name', 255)->change();
                $table->string('middle_name', 255)->nullable()->change();
                $table->string('last_name', 255)->nullable()->change();
                $table->string('maiden_name', 255)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
                $table->string('name', 1000)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'addresses', function (Blueprint $table) {
                $table->string('address', 255)->nullable()->change();
                $table->string('line1', 255)->change();
                $table->string('line2', 255)->nullable()->change();
                $table->string('line3', 255)->nullable()->change();
                $table->string('code', 255)->nullable()->change();
                $table->string('city', 255)->nullable()->change();
                $table->string('state', 255)->nullable()->change();
                $table->string('country', 255)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
                $table->string('address', 255)->nullable()->change();
            });

            Schema::table(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
                $table->string('number', 255)->nullable()->change();
            });
        }
    }
}
