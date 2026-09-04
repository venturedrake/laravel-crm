<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;
use VentureDrake\LaravelCrm\Support\PdfLogo;
use VentureDrake\LaravelCrm\Support\PdfSampleData;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/**
 * Renders a live PDF preview of any (docType × template slug) pair against
 * fabricated sample data.
 *
 * Powers the "Preview" affordance on the Settings > Templates picker so an
 * admin can see exactly what a given template variant looks like for their
 * chosen doc type before persisting a preference. All rendering runs
 * against `PdfSampleData` — no real records are touched and no writes
 * fire, so previews work on a brand-new install with an empty database.
 *
 * Unknown docType or slug values return a 404 rather than silently
 * falling through to a default; the caller is expected to always pass
 * one of `PdfTemplateRegistry::DOC_TYPES` and a slug present in
 * `PdfTemplateRegistry::all()`.
 */
class TemplatePreviewController extends Controller
{
    /**
     * The 5 template slugs a preview can be rendered against.
     *
     * Extracted from `PdfTemplateRegistry::all()` at request time so any
     * future template addition is picked up automatically.
     *
     * @return array<int, string>
     */
    protected function knownSlugs(): array
    {
        return array_keys(PdfTemplateRegistry::all());
    }

    /**
     * Stream the preview PDF inline for `{docType}` rendered with `{slug}`.
     *
     * Called via `GET /settings/templates/preview/{docType}/{slug}` under
     * the settings-permission middleware group. The response's
     * `Content-Type` is `application/pdf` and the body is the raw PDF
     * bytes — browsers render it inline in their PDF viewer rather
     * than triggering a download prompt.
     */
    public function show(string $docType, string $slug)
    {
        if (! in_array($docType, PdfTemplateRegistry::DOC_TYPES, true)) {
            throw new NotFoundHttpException('Unknown PDF doc type.');
        }

        if (! in_array($slug, $this->knownSlugs(), true)) {
            throw new NotFoundHttpException('Unknown PDF template slug.');
        }

        $data = $this->sampleData($docType);

        // Lock paper to A4 portrait so the container-document's fixed
        // 18.6cm width (declared in layouts/document.blade.php) fills the
        // printable area edge-to-edge. Without an explicit setPaper()
        // call DomPDF falls through to its default paper (usually Letter
        // portrait in landscape rendering) which leaves ~40% of the page
        // width empty because the fixed-cm container hugs the left edge.
        $pdf = Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])->setPaper('a4', 'portrait')
            ->loadView(PdfTemplateRegistry::viewFor($docType, $slug), $data);

        return $pdf->stream('preview-'.$docType.'-'.$slug.'.pdf');
    }

    /**
     * Serve the picker thumbnail SVG for `{slug}`.
     *
     * Called via `GET /settings/templates/thumbnail/{slug}` from the
     * Templates picker's `<img>` tags. Resolving through the registry
     * (published copy first, packaged copy second) rather than linking
     * `asset()` directly means the picker still renders artwork on a host
     * whose published assets predate these thumbnails — the previous
     * `asset()` link 404'd there and the picker fell back to text-only
     * placeholders.
     *
     * The SVGs are identical for every user and carry no tenant data, so
     * they are safe to cache publicly for a day.
     */
    public function thumbnail(string $slug)
    {
        $path = PdfTemplateRegistry::thumbnailFile($slug);

        if ($path === null) {
            throw new NotFoundHttpException('Unknown PDF template thumbnail.');
        }

        return response()->file($path, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Build the view data array for a given doc type, mirroring the
     * variable set the real download controllers pass to their Blade
     * templates. Every model in the returned array is unsaved (via
     * `PdfSampleData`) so no DB writes ever fire.
     *
     * The settings-driven variables (dateFormat / taxName / etc.) fall
     * back to sensible defaults when the host hasn't seeded any Setting
     * rows — critical for the AC's "brand-new install with zero real
     * records" contract.
     *
     * @return array<string, mixed>
     */
    protected function sampleData(string $docType): array
    {
        $settings = app('laravel-crm.settings');

        $common = [
            'dateFormat' => $settings->get('date_format', config('laravel-crm.date_format', 'M j, Y')),
            'taxName' => $settings->get('tax_name', 'Tax'),
            'contactDetails' => PdfContactDetails::for($docType),
            'paymentInstructions' => $settings->get('invoice_payment_instructions', null),
            'fromName' => $settings->get('organization_name', 'Sample Organization'),
            'logo' => PdfLogo::src($settings->get('logo_file', null)),
            'email' => null,
            'phone' => null,
            'address' => PdfSampleData::address(),
            'organization_address' => PdfSampleData::address(),
        ];

        switch ($docType) {
            case 'invoice':
                $invoice = PdfSampleData::invoice();

                return array_merge($common, ['invoice' => $invoice]);

            case 'order':
                $order = PdfSampleData::order();

                return array_merge($common, ['order' => $order]);

            case 'purchase-order':
                $purchaseOrder = PdfSampleData::purchaseOrder();

                return array_merge($common, ['purchaseOrder' => $purchaseOrder]);

            case 'delivery':
                $delivery = PdfSampleData::delivery();

                // The delivery blade also expects the parent order shape;
                // reuse the sample order so its metadata (reference,
                // subtotal, etc.) surfaces alongside the delivery.
                return array_merge($common, [
                    'delivery' => $delivery,
                    'order' => PdfSampleData::order(),
                ]);

            case 'quote':
                $quote = PdfSampleData::quote();

                return array_merge($common, ['quote' => $quote]);
        }

        // Unreachable — docType is validated against DOC_TYPES above,
        // but PHP's static analyzer needs the explicit fallthrough.
        throw new NotFoundHttpException('Unknown PDF doc type.');
    }
}
