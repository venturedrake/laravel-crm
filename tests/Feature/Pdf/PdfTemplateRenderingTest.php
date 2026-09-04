<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use VentureDrake\LaravelCrm\Support\PdfSampleData;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/*
 * Feature coverage for every (docType × template slug) pair the plugin
 * ships. The suite exercises the preview route end-to-end for all 25
 * combinations (5 doc types × 5 templates), plus locks two AC-mandated
 * regression contracts:
 *
 *  1. Missing `pdf_template_{docType}` setting → registry falls back to
 *     the `modern` default AND the preview route still returns a valid
 *     PDF.
 *  2. The `classic` template's wrapper blade still renders the existing
 *     `laravel-crm::{entity}/pdf.blade.php` content byte-for-byte via
 *     Blade's `@include` pass-through.
 *
 * Delivery is gated on `Schema::hasTable('crm_delivery_products')` per
 * the TemplatePreviewControllerTest precedent — the core TestSchema
 * omits the delivery-products table, so production hosts exercise the
 * full 25-pair grid while test environments cover the 20 non-delivery
 * pairs. No pixel diffing — assertions are on HTTP status, content
 * type, and non-empty binary per the AC.
 */

beforeEach(function () {
    // Barryvdh's DomPDF ServiceProvider isn't in the test suite's
    // package-provider list; register it here so `Pdf::loadView(...)`
    // resolves the `dompdf.wrapper` container binding at request time.
    // Same discipline as TemplatePreviewControllerTest.
    $this->app->register(DomPdfServiceProvider::class);

    $this->actingAsUser(['crm_access' => 1]);
    // Grant the settings-permission gate so the middleware chain lets
    // the request reach the controller.
    Gate::before(fn () => true);
});

/**
 * Dataset: every (docType, slug) combination declared by the registry.
 *
 * Always yields the full 25 rows (5 doc types × 5 templates) because
 * Pest dataset closures run during test discovery — BEFORE the app is
 * booted — so we can't call `Schema::hasTable` here. The per-row test
 * body gates delivery via a `Schema::hasTable` check at run time; test
 * environments without `crm_delivery_products` see the 5 delivery
 * rows marked as skipped rather than filtered out. Keys are
 * human-readable so pest output identifies the failing combo directly
 * (e.g. "invoice-modern").
 */
dataset('template_docType_pairs', function () {
    // Slugs hardcoded to avoid calling PdfTemplateRegistry::all() at
    // test-discovery time — all() invokes __() for label resolution,
    // and the translator facade isn't wired up before the app boots.
    // The hardcoded list mirrors the 5 slugs the registry declares
    // (verified by tests/Feature/Support/PdfTemplateRegistryTest.php).
    $slugs = ['modern', 'classic', 'bold', 'compact', 'professional'];

    $rows = [];
    foreach (PdfTemplateRegistry::DOC_TYPES as $docType) {
        foreach ($slugs as $slug) {
            $rows[$docType.'-'.$slug] = [$docType, $slug];
        }
    }

    return $rows;
});

test('every (docType × template) preview route returns a valid non-empty PDF', function (string $docType, string $slug) {
    // The delivery blade reads from `deliveryProducts()` as a live query
    // builder (not the pre-loaded relation), so exercising it requires
    // the `crm_delivery_products` table — absent from the core
    // TestSchema. Skip delivery in test environments that lack it;
    // production hosts (which ship the full schema) exercise all 25.
    if ($docType === 'delivery' && ! Schema::hasTable('crm_delivery_products')) {
        $this->markTestSkipped('crm_delivery_products table not present in this test schema');
    }

    $response = $this->get(route('laravel-crm.settings.templates.preview', [
        'docType' => $docType,
        'slug' => $slug,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');

    $body = $response->getContent();

    // AC: assert the returned PDF binary is non-empty AND begins with
    // the `%PDF-` magic bytes. Locks that DomPDF produced a real PDF
    // rather than an error page cast to bytes.
    expect(strlen($body))->toBeGreaterThan(0);
    expect(substr($body, 0, 5))->toBe('%PDF-');
})->with('template_docType_pairs');

test('default template slug is `modern` when no setting is persisted', function () {
    // Locks the registry-level default. The download / send / portal
    // controllers all read via
    // `SettingService::get('pdf_template_X', PdfTemplateRegistry::defaultSlug())`
    // so this guarantees a brand-new install (no Setting rows) renders
    // the Modern template rather than 500'ing on an unresolved slug.
    expect(PdfTemplateRegistry::defaultSlug())->toBe('modern');

    // Round-trip: viewFor with an empty slug argument (the shape a
    // missing setting collapses to) falls through to defaultSlug.
    expect(PdfTemplateRegistry::viewFor('invoice', ''))
        ->toBe('laravel-crm::pdfs.modern.invoice');

    // End-to-end: the preview route for every doc type against the
    // default slug returns a valid PDF, satisfying the "brand-new
    // install works" contract.
    $renderable = Schema::hasTable('crm_delivery_products')
        ? PdfTemplateRegistry::DOC_TYPES
        : array_values(array_filter(
            PdfTemplateRegistry::DOC_TYPES,
            fn (string $t) => $t !== 'delivery',
        ));

    foreach ($renderable as $docType) {
        $response = $this->get(route('laravel-crm.settings.templates.preview', [
            'docType' => $docType,
            'slug' => PdfTemplateRegistry::defaultSlug(),
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
});

test('every new template renders sample line items — guards against the empty-table regression', function () {
    // Locks that PdfSampleData's fabricated line items actually surface
    // in each new template's rendered HTML. Renders via View::make (not
    // through DomPDF) so the assertion runs against readable HTML rather
    // than opaque PDF bytes — the DomPDF path is covered separately by
    // the (docType × slug) parametric test above.
    $newSlugs = ['modern', 'bold', 'compact', 'professional'];

    $perDocType = [
        'invoice' => [
            'invoice' => PdfSampleData::invoice(),
        ],
        'order' => [
            'order' => PdfSampleData::order(),
        ],
        'purchase-order' => [
            'purchaseOrder' => PdfSampleData::purchaseOrder(),
        ],
        'quote' => [
            'quote' => PdfSampleData::quote(),
        ],
        // The delivery blades read the parent order's metadata alongside the
        // delivery's own line items, so they need both — mirroring what
        // TemplatePreviewController::sampleData() assembles for this doc type.
        'delivery' => [
            'delivery' => PdfSampleData::delivery(),
            'order' => PdfSampleData::order(),
        ],
    ];

    $common = [
        'dateFormat' => 'M j, Y',
        'taxName' => 'Tax',
        'contactDetails' => null,
        'paymentInstructions' => null,
        'fromName' => 'Sample Organization',
        'logo' => null,
        'email' => null,
        'phone' => null,
        'address' => PdfSampleData::address(),
        'organization_address' => PdfSampleData::address(),
    ];

    foreach ($perDocType as $docType => $entity) {
        foreach ($newSlugs as $slug) {
            $html = View::make('laravel-crm::pdfs.'.$slug.'.'.$docType, array_merge($common, $entity))->render();

            expect($html)->toContain('Sample product A');
            expect($html)->toContain('Sample product B');
        }
    }
});

test('classic template renders the existing pdf.blade.php content via @include pass-through', function () {
    // The Classic wrappers are one-line
    // `@include('laravel-crm::{entity}/pdf')` blades (US-002 of the
    // pdf-templates series). Rendering the wrapper directly via Blade
    // should produce identical output to rendering the wrapped view
    // directly with the same data — Blade's @include pipes the parent
    // scope through verbatim.
    $sample = [
        'invoice' => PdfSampleData::invoice(),
        'dateFormat' => 'M j, Y',
        'taxName' => 'Tax',
        'contactDetails' => null,
        'paymentInstructions' => null,
        'fromName' => 'Sample Organization',
        'logo' => null,
        'email' => null,
        'phone' => null,
        'address' => PdfSampleData::address(),
        'organization_address' => PdfSampleData::address(),
    ];

    $wrapperOutput = View::make('laravel-crm::pdfs.classic.invoice', $sample)->render();
    $directOutput = View::make('laravel-crm::invoices.pdf', $sample)->render();

    // Byte-for-byte identical because Blade's @include compiles the
    // included view into the wrapper's compiled output verbatim,
    // inheriting the caller's data array.
    expect($wrapperOutput)->toBe($directOutput);

    // Positive-presence guard: the classic wrapper's rendered HTML
    // contains identifiable markers from the sample data. If the
    // @include were silently skipped, the output would still equal
    // itself but wouldn't contain any sample content.
    expect($wrapperOutput)->toContain('INV-SAMPLE');
    expect($wrapperOutput)->toContain('Acme Sample Co.');

    // Sanity check: the classic preview route also produces a valid
    // PDF for the invoice doc type, confirming end-to-end that the
    // @include-driven Classic template renders through the same
    // DomPDF path as the other 4 templates.
    $response = $this->get(route('laravel-crm.settings.templates.preview', [
        'docType' => 'invoice',
        'slug' => 'classic',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(0);
});
