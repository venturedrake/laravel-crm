<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLabelEditableFieldsToLaravelCrmSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'settings', function (Blueprint $table) {
            $table->string('label')->after('name')->nullable();
            $table->boolean('editable')->after('value')->default(false);
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
            $table->dropColumn([
                'label',
                'editable'
            ]);
        });
    }
}
