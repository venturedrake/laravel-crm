<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Before part payments, InvoiceService::create() left amount_due NULL and the
 * Pay drawer validated the payment against it - so the only amount it took
 * was 0. "Paying" an invoice that way stamped fully_paid_at but recorded
 * nothing paid: amount_paid 0, amount_due 0. The forms still offer Edit on
 * those invoices (they gate it on amount_paid), and their recorded balance
 * read as nothing paid against the whole total.
 *
 * Record them as paid in full, which is what marking them paid meant. Any
 * balance stored below zero is also brought up to it - InvoiceService never
 * writes one now, and the model's accessor reads a negative balance as the
 * whole total still due.
 *
 * Runs after 2026_11_01_000001, which fills in the NULL balances first.
 * down() is a no-op - the zeros carried no information to restore.
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
            || ! Schema::hasColumn($table, 'amount_paid')
            || ! Schema::hasColumn($table, 'fully_paid_at')) {
            return;
        }

        DB::table($table)
            ->whereNotNull('fully_paid_at')
            ->where(fn ($query) => $query->whereNull('amount_paid')->orWhere('amount_paid', 0))
            ->update([
                'amount_paid' => DB::raw('COALESCE(total, 0)'),
                'amount_due' => 0,
            ]);

        DB::table($table)
            ->where('amount_due', '<', 0)
            ->update(['amount_due' => 0]);
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
