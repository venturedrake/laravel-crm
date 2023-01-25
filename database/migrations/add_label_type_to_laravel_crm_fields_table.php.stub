<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLabelTypeToLaravelCrmFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
             $table->enum('type', [
                'text',
                'textarea',
                'select',
                'select_multiple',
                'checkbox',
                'radio',
                'date'
             ])->after('team_id')->default('text');
             $table->string('handle')->after('name')->nullable();
             $table->boolean('required')->after('handle')->default(false);
             $table->string('default')->after('required')->nullable();
             $table->json('config')->after('default')->nullable();
             $table->json('validation')->after('config')->nullable();
            
             $table->dropColumn([
                'model',
                'key'
             ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'fields', function (Blueprint $table) {
            $table->dropColumn([
                'handle',
                'type',
                'required',
                'default',
                'config',
                'validation'
            ]);
            
            $table->string('model')->nullable();
            $table->string('key')->nullable();
        });
    }
}
