<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
            Schema::create(
                config('laravel-crm.db_table_prefix').'settings',
                function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('value');
                $table->timestamps();
            }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
