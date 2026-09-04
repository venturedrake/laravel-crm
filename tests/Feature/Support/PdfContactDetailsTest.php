<?php

use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;

/*
 * The "From" contact block chain: {doc_type}_contact_details →
 * pdf_contact_details → null.
 *
 * The per-doc-type key deliberately wins so hosts upgrading from a release
 * where only `invoice_contact_details` existed keep byte-identical invoice
 * output, while every other doc type picks up the shared key that previously
 * had nowhere to come from.
 */

beforeEach(function () {
    Setting::query()->delete();
    app('laravel-crm.settings')->forgetCache();
});

/**
 * Write a setting and drop the memoised map, mirroring what a real save
 * through SettingEdit does.
 */
function seedContactSetting(string $name, $value): void
{
    app('laravel-crm.settings')->set($name, $value);
    app('laravel-crm.settings')->forgetCache();
}

test('returns null when neither the per-doc-type nor the shared key is set', function () {
    foreach (['quote', 'order', 'delivery', 'invoice', 'purchase-order'] as $docType) {
        expect(PdfContactDetails::for($docType))->toBeNull();
    }
});

test('the per-doc-type key wins over the shared key', function () {
    seedContactSetting(PdfContactDetails::SHARED_KEY, 'Shared block');
    seedContactSetting('invoice_contact_details', 'Invoice-only block');

    expect(PdfContactDetails::for('invoice'))->toBe('Invoice-only block');
});

test('the shared key is used when the doc type has no override of its own', function () {
    seedContactSetting(PdfContactDetails::SHARED_KEY, 'Shared block');
    seedContactSetting('invoice_contact_details', 'Invoice-only block');

    // The doc types that never had a settings field of their own — the
    // shared key is the only way their "From" block can be filled.
    foreach (['quote', 'order', 'delivery'] as $docType) {
        expect(PdfContactDetails::for($docType))->toBe('Shared block');
    }
});

test('an empty per-doc-type value falls through to the shared key', function () {
    // The legacy SettingController persists whatever the form posts, so a
    // host that filled the invoice field and then cleared it has an
    // empty-string row rather than no row. `??` would treat that as "set"
    // and pin the chain on '', silently swallowing the shared value.
    seedContactSetting(PdfContactDetails::SHARED_KEY, 'Shared block');
    seedContactSetting('invoice_contact_details', '');

    expect(PdfContactDetails::for('invoice'))->toBe('Shared block');
});

test('an empty shared value resolves to null rather than an empty string', function () {
    // The blades branch on `@if($contactDetails ?? null)`, so '' would fall
    // to $fromName anyway — but returning null keeps the contract honest for
    // any caller that checks with is_null().
    seedContactSetting(PdfContactDetails::SHARED_KEY, '');

    expect(PdfContactDetails::for('quote'))->toBeNull();
});

test('purchase-order normalises its hyphen to the underscored settings name', function () {
    // The doc type is a hyphenated slug; the settings table keys on
    // snake_case. `purchase_order_contact_details` was already read by three
    // call sites but written by no screen — this is what makes it reachable.
    expect(PdfContactDetails::settingKey('purchase-order'))
        ->toBe('purchase_order_contact_details');

    seedContactSetting('purchase_order_contact_details', 'PO block');

    expect(PdfContactDetails::for('purchase-order'))->toBe('PO block');
});

test('settingKey derives the expected name for every doc type', function () {
    expect(PdfContactDetails::settingKey('quote'))->toBe('quote_contact_details');
    expect(PdfContactDetails::settingKey('order'))->toBe('order_contact_details');
    expect(PdfContactDetails::settingKey('delivery'))->toBe('delivery_contact_details');
    expect(PdfContactDetails::settingKey('invoice'))->toBe('invoice_contact_details');
});
