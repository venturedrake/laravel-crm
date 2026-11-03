<?php

/*
 * backfill_amount_due_on_laravel_crm_invoices_table - gives the invoices the
 * old InvoiceService::create() left without a balance one, so they can be
 * paid.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function runAmountDueBackfill(): void
{
    $migration = require __DIR__.'/../../database/updates/2026_11_01_000001_backfill_amount_due_on_laravel_crm_invoices_table.php';

    $migration->up();
}

function invoiceRow(array $attributes): int
{
    return DB::table(config('laravel-crm.db_table_prefix').'invoices')->insertGetId($attributes + [
        'external_id' => (string) Str::uuid(),
        'invoice_id' => 'INV-'.uniqid(),
        'currency' => 'USD',
    ]);
}

function invoiceBalance(int $id): array
{
    $row = DB::table(config('laravel-crm.db_table_prefix').'invoices')->find($id);

    return [$row->amount_due, $row->amount_paid];
}

test('an invoice with no balance is given one', function () {
    $unpaid = invoiceRow(['total' => 22000, 'amount_due' => null, 'amount_paid' => null]);
    $partPaid = invoiceRow(['total' => 22000, 'amount_due' => null, 'amount_paid' => 5000]);
    $empty = invoiceRow(['total' => null, 'amount_due' => null, 'amount_paid' => null]);
    $deleted = invoiceRow(['total' => 1000, 'amount_due' => null, 'amount_paid' => null, 'deleted_at' => now()]);

    runAmountDueBackfill();

    expect(invoiceBalance($unpaid))->toEqual([22000, 0])
        ->and(invoiceBalance($partPaid))->toEqual([17000, 5000])
        ->and(invoiceBalance($empty))->toEqual([0, 0])
        ->and(invoiceBalance($deleted))->toEqual([1000, 0]);
});

test('an invoice that already has a balance is left alone', function () {
    // A balance that disagrees with the total on purpose - the backfill must
    // not recompute it.
    $id = invoiceRow(['total' => 22000, 'amount_due' => 100, 'amount_paid' => 300]);

    runAmountDueBackfill();

    expect(invoiceBalance($id))->toEqual([100, 300]);
});

test('running it twice changes nothing more', function () {
    $id = invoiceRow(['total' => 22000, 'amount_due' => null, 'amount_paid' => null]);

    runAmountDueBackfill();
    runAmountDueBackfill();

    expect(invoiceBalance($id))->toEqual([22000, 0]);
});

/*
 * settle_legacy_paid_invoices - an invoice "paid" through the old drawer,
 * which only took 0, is recorded as paid in full.
 */

function runSettleLegacyPaid(): void
{
    $migration = require __DIR__.'/../../database/updates/2026_11_01_000002_settle_legacy_paid_invoices_on_laravel_crm_invoices_table.php';

    $migration->up();
}

test('an invoice marked paid with nothing recorded is recorded as paid in full', function () {
    $legacy = invoiceRow(['total' => 22000, 'amount_due' => 0, 'amount_paid' => 0, 'fully_paid_at' => now()]);
    $unpaid = invoiceRow(['total' => 22000, 'amount_due' => 22000, 'amount_paid' => 0]);
    $paid = invoiceRow(['total' => 22000, 'amount_due' => 0, 'amount_paid' => 22000, 'fully_paid_at' => now()]);

    runSettleLegacyPaid();

    expect(invoiceBalance($legacy))->toEqual([0, 22000])
        ->and(invoiceBalance($unpaid))->toEqual([22000, 0])
        ->and(invoiceBalance($paid))->toEqual([0, 22000]);
});

test('a balance below zero is brought up to zero', function () {
    $id = invoiceRow(['total' => -5000, 'amount_due' => -5000, 'amount_paid' => 0]);

    runSettleLegacyPaid();

    expect(invoiceBalance($id))->toEqual([0, 0]);
});
