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

/*
 * The line-height correction.
 *
 * DomPDF multiplies the font's own height by a unitless line-height instead of
 * using it as the line box height, so a template declaring 1.2 renders at
 * ~1.79em with Nunito while a browser gives it exactly 1.2em. The correction is
 * applied on the way out rather than in the templates, because raising the
 * declared value would inflate the PDF by the same factor again — verified by
 * rasterising DomPDF output before and after.
 */

test('it scales the rendered template\'s line-height to match DomPDF', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2004',
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'modern'),
        portalDocumentInvoiceData($invoice)
    );

    // modern declares 1.2 on .modern-pdf; 1.2 * 1.49 = 1.788.
    expect($html)->toContain('.container-document .modern-pdf{line-height:1.788}');
});

test('it corrects only the template being rendered', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2005',
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'bold'),
        portalDocumentInvoiceData($invoice)
    );

    // bold declares 1.3 on its wrapper and overrides the meta rows at 1.15.
    expect($html)->toContain('.container-document .bold-pdf{line-height:1.937}')
        ->and($html)->toContain('.container-document .bold-pdf .bold-meta td{line-height:1.713}')
        ->and($html)->not->toContain('modern-pdf{line-height');
});

test('it leaves the classic template alone', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2006',
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'classic'),
        portalDocumentInvoiceData($invoice)
    );

    // Classic renders the pre-picker legacy views through the bundled
    // Bootstrap build, which this correction has not been measured against.
    // (Looks for the injected block specifically: the layout inlines the
    // bundled Bootstrap build, so `line-height:` occurs all over the head
    // regardless.)
    expect($html)->not->toContain('"><style>.container-document');
});

test('the correction outranks the declaration it is correcting', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV2007',
        'currency' => 'USD',
    ]);

    $html = PortalDocument::html(
        PdfTemplateRegistry::viewFor('invoice', 'modern'),
        portalDocumentInvoiceData($invoice)
    );

    // The template's own <style> sits in the body, after this one, so the
    // correction only wins on specificity — hence the .container-document
    // prefix. If that prefix is ever dropped the override silently stops
    // applying, with nothing else to catch it.
    $correction = strpos($html, '.container-document .modern-pdf{line-height:');
    $template = strpos($html, '.modern-pdf {');

    expect($correction)->toBeLessThan($template);
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
