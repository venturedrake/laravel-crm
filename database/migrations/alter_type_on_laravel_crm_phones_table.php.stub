<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTypeOnLaravelCrmPhonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE ".config('laravel-crm.db_table_prefix')."phones CHANGE COLUMN type type ENUM('work','home','mobile','fax','other') NOT NULL DEFAULT 'work'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE ".config('laravel-crm.db_table_prefix')."phones CHANGE COLUMN type type ENUM('work','home','mobile','other') NOT NULL DEFAULT 'work'");
    }
}
