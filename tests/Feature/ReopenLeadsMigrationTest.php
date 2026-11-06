<?php

/*
 * reopen_leads_whose_deals_were_deleted_on_laravel_crm_leads_table - frees the
 * leads whose deal was deleted before DealObserver cleared converted_at, which
 * could otherwise never be converted again.
 */

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;

function runReopenLeads(): void
{
    $migration = require __DIR__.'/../../database/updates/2026_11_06_000001_reopen_leads_whose_deals_were_deleted_on_laravel_crm_leads_table.php';

    $migration->up();
}

function convertedLead(): Lead
{
    return Lead::create(['title' => 'A lead', 'currency' => 'USD', 'converted_at' => '2026-01-02 03:04:05']);
}

function dealFor(Lead $lead, bool $deleted = false): Deal
{
    $deal = Deal::create(['title' => 'A deal', 'lead_id' => $lead->id, 'currency' => 'USD']);

    // As stored by a delete from before the observer did anything about it.
    if ($deleted) {
        Deal::whereKey($deal->id)->toBase()->update(['deleted_at' => now()]);
    }

    return $deal;
}

test('a lead whose only deal was deleted is reopened', function () {
    $lead = convertedLead();
    dealFor($lead, deleted: true);

    runReopenLeads();

    expect($lead->fresh()->isConverted())->toBeFalse();
});

test('a lead with a deal left stays converted', function () {
    $lead = convertedLead();
    dealFor($lead);
    dealFor($lead, deleted: true);

    runReopenLeads();

    expect($lead->fresh()->isConverted())->toBeTrue();
});

test('a lead with no deals at all stays converted', function () {
    $lead = convertedLead();

    runReopenLeads();

    expect($lead->fresh()->isConverted())->toBeTrue();
});

test('a deleted lead is left alone', function () {
    $lead = convertedLead();
    dealFor($lead, deleted: true);
    Lead::whereKey($lead->id)->toBase()->update(['deleted_at' => now()]);

    runReopenLeads();

    expect(Lead::withTrashed()->find($lead->id)->isConverted())->toBeTrue();
});

test('running it twice changes nothing more', function () {
    $reopened = convertedLead();
    dealFor($reopened, deleted: true);
    $kept = convertedLead();
    dealFor($kept);

    runReopenLeads();
    runReopenLeads();

    expect($reopened->fresh()->isConverted())->toBeFalse()
        ->and($kept->fresh()->converted_at->format('Y-m-d H:i:s'))->toBe('2026-01-02 03:04:05');
});
