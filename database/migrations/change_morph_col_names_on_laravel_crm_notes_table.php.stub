<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMorphColNamesOnLaravelCrmNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'notes', function (Blueprint $table) {
            if (! Schema::hasColumn(config('laravel-crm.db_table_prefix').'notes', 'noteable_type')){
                $table->renameColumn(config('laravel-crm.db_table_prefix').'noteable_type', 'noteable_type');
            }
            
            if (! Schema::hasColumn(config('laravel-crm.db_table_prefix').'notes', 'noteable_id')){
                $table->renameColumn(config('laravel-crm.db_table_prefix').'noteable_id', 'noteable_id');
            }
        });
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
