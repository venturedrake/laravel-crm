<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrefixToLaravelCrmInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
            $table->string('prefix')->after('invoice_id')->nullable();
            $table->renameColumn('invoice_number', 'number');
            $table->dropColumn([
                'description',
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
        Schema::table(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
            $table->dropColumn([
                'prefix',
            ]);     
            $table->renameColumn('number', 'invoice_number');
            $table->text('description')->after('organisation_id')->nullable();
        });
    }
}
