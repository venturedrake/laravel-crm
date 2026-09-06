<?php

use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\PortalDocument;

/*
 * PortalDocument renders a PDF template for embedding in a portal page.
 *
 * The interesting part is the injected <base href>: a `srcdoc` iframe inherits
 * the parent page's URL as its base, so the document layout's relative
 * `vendor/laravel-crm/fonts/Nunito-*.ttf` @font-face sources would resolve
 * against `/p/invoices/{id}` and 404 — silently dropping Nunito and making the
 * web view diverge from the PDF it is supposed to mirror.
 */

/**
 * The view data every invoice template reads, mirroring what the portal
 * controller passes.
 *
 * @return array<string, mixed>
 */
function portalDocumentInvoiceData(Invoice $invoice): array
{
    return [
        'invoice' => $invoice,
        'dateFormat' => 'd/m/Y',
        'taxName' => 'Tax',
        'contactDetails' => null,
        'paymentInstructions' => null,
        'email' => null,
        'phone' => null,
        'address' => null,
        'organization_address' => null,
        'fromName' => 'Acme',
        'logo' => null,
    ];
}

test('it renders the requested template', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2001',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'bold'),
        portalDocumentInvoiceData($invoice)
    );

    expect($html)->toContain('bold-pdf');
    expect($html)->toContain(ucfirst(__('laravel-crm::lang.sub_total')));
    expect($html)->toContain((string) money($invoice->total, $invoice->currency));
});

test('it injects a base href pointing at the app root', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2002',
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'modern'),
        portalDocumentInvoiceData($invoice)
    );

    expect($html)->toContain('<base href="'.rtrim(url('/'), '/').'/">');

    // Immediately after <head>, so it precedes every relative URL in the
    // document — the @font-face sources in particular.
    expect($html)->toMatch('/<head[^>]*>\s*<base href=/');
});

test('it injects the base exactly once', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2003',
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'modern'),
        portalDocumentInvoiceData($invoice)
    );

    expect(substr_count($html, '<base href='))->toBe(1);
});

test('the shared document layout stays free of an absolute base', function () {
    // DomPDF resolves the layout's relative font paths itself, with
    // `dompdf.enable_remote` off — adding a <base> to the shared layout would
    // change font resolution for every PDF the package renders. The browser-
    // only fixup has to stay in PortalDocument.
    $layout = file_get_contents(
        __DIR__.'/../../../resources/views/layouts/document.blade.php'
    );

    expect($layout)->not->toContain('<base');
});
