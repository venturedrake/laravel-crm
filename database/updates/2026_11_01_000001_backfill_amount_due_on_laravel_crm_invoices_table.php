<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * InvoiceService::create() never set amount_due or amount_paid, so every
 * invoice created from the form has both NULL. The model reads a NULL
 * amount_due as unset, the Pay drawer validated against it, and the
 * dashboard's outstanding total skipped those rows.
 *
 * Only rows still NULL are touched: an invoice that has been edited or paid
 * since already carries a balance of its own. Soft-deleted rows are included
 * so a restore brings back a consistent invoice.
 *
 * down() is a no-op - the NULLs carried no information to restore.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = config('laravel-crm.db_table_prefix').'invoices';

        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'amount_due')
            || ! Schema::hasColumn($table, 'amount_paid')) {
            return;
        }

        DB::table($table)
            ->whereNull('amount_due')
            // amount_due first: MySQL applies the assignments left to right,
            // so it must read amount_paid before that is rewritten.
            ->update([
                'amount_due' => DB::raw('COALESCE(total, 0) - COALESCE(amount_paid, 0)'),
                'amount_paid' => DB::raw('COALESCE(amount_paid, 0)'),
            ]);
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
};
