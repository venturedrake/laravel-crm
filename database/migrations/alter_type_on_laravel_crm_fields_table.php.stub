<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTypeOnLaravelCrmFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE ".config('laravel-crm.db_table_prefix')."fields CHANGE COLUMN type type ENUM('text','textarea','select','select_multiple','checkbox','checkbox_multiple', 'radio','date') NOT NULL DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE ".config('laravel-crm.db_table_prefix')."fields CHANGE COLUMN type type ENUM('text','textarea','select','select_multiple','checkbox', 'radio','date') NOT NULL DEFAULT 'text'");
    }
}
