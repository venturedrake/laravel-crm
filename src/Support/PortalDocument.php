<?php

namespace VentureDrake\LaravelCrm\Support;

/**
 * Renders a PDF template for embedding in a portal page.
 *
 * The public portal pages show the customer the *same* document they get when
 * they hit Download, by rendering the record's chosen PDF template into an
 * iframe rather than hand-rolling a second, divergent HTML version of it.
 *
 * Callers resolve the view with `PdfTemplateRegistry::viewForModel(...)` — the
 * same call the download path makes — and pass the same view data, so the two
 * renderings cannot drift apart.
 *
 * What lives here is the small set of adjustments that are *only* correct in a
 * browser. Each one exists because DomPDF and a browser genuinely disagree, and
 * each is applied on the way out rather than in the shared templates, so the
 * PDFs the package generates are byte-for-byte unaffected.
 */
class PortalDocument
{
    /**
     * How much taller DomPDF's line boxes are than a browser's, for Nunito.
     *
     * DomPDF does not treat a unitless `line-height` as the line box height the
     * way CSS says it should. It multiplies the *font's own* height by that
     * ratio, so a template declaring `line-height: 1.2` gets 1.2 × Nunito's
     * natural 1.49em ≈ 1.79em per line. A browser gives it exactly 1.2em, which
     * is why the portal page stacked the TO/FROM address lines visibly tighter
     * than the PDF of the same document.
     *
     * Measured against real DomPDF output rasterised at 96 DPI (so 1 image
     * pixel = 1 CSS pixel), on two templates with different type scales:
     *
     *   modern  13px text, declares 1.2  → 23.3px line pitch → 1.49
     *   bold    12px text, declares 1.3  → 23.4px line pitch → 1.50
     *
     * Re-measure this if the document layout's bundled Nunito faces are ever
     * swapped — it is a property of the font's metrics, not of any template.
     */
    protected const DOMPDF_LINE_BOX_FACTOR = 1.49;

    /**
     * The unitless `line-height` each template declares, and the selector it
     * declares it on, keyed by template slug.
     *
     * Only rules a browser would otherwise apply *differently* to DomPDF need
     * listing; everything else inherits from the template's wrapper. `classic`
     * is deliberately absent — it renders the pre-picker legacy views through
     * the bundled Bootstrap build, whose line spacing this has not been
     * measured against.
     *
     * @var array<string, array<string, float>>
     */
    protected const TEMPLATE_LINE_HEIGHTS = [
        'modern' => ['.modern-pdf' => 1.2],
        'bold' => ['.bold-pdf' => 1.3, '.bold-pdf .bold-meta td' => 1.15],
        'compact' => ['.compact-pdf' => 1.25],
        'professional' => ['.professional-pdf' => 1.3],
    ];

    /**
     * Render `$view` with `$data` and return HTML suitable for a browser.
     *
     * @param  string  $view  a `laravel-crm::pdfs.*` view path
     * @param  array<string, mixed>  $data
     */
    public static function html(string $view, array $data): string
    {
        $html = view($view, $data)->render();

        return static::injectIntoHead($html, static::baseTag().static::lineHeightFix($view));
    }

    /**
     * An absolute `<base>` pointing at the app root.
     *
     * A `srcdoc` iframe inherits the *parent* page's URL as its base, so the
     * font-face sources in the document layout — relative paths of the form
     * `vendor/laravel-crm/fonts/Nunito-*.ttf` — would otherwise resolve
     * against `/p/invoices/{id}` and 404, silently dropping Nunito and making
     * the web view diverge from the PDF it is meant to mirror.
     *
     * This has to happen here rather than in `layouts/document.blade.php`,
     * because DomPDF resolves those same relative font paths itself against
     * its own base, with `dompdf.enable_remote` disabled — adding an absolute
     * `<base>` to the shared layout would change font resolution for every PDF
     * the package renders.
     */
    protected static function baseTag(): string
    {
        return '<base href="'.e(rtrim(url('/'), '/').'/').'">';
    }

    /**
     * A `<style>` restating the rendered template's own line-heights, scaled by
     * DOMPDF_LINE_BOX_FACTOR so the browser produces the same line boxes the
     * PDF does.
     *
     * Only the rules for the template actually being rendered are emitted, so
     * the embedded document never mentions a template it is not using.
     *
     * The selectors are prefixed with `.container-document` (the document
     * layout's wrapper) purely to outrank the template's own declaration: the
     * template's `<style>` block sits in the body, *after* this one, so at
     * equal specificity it would win on source order.
     *
     * @param  string  $view  a `laravel-crm::pdfs.<slug>.<docType>` view path
     */
    protected static function lineHeightFix(string $view): string
    {
        $slug = static::slugFor($view);
        $rules = static::TEMPLATE_LINE_HEIGHTS[$slug] ?? [];

        if ($rules === []) {
            return '';
        }

        $css = '';

        foreach ($rules as $selector => $declared) {
            $css .= sprintf(
                '.container-document %s{line-height:%s}',
                $selector,
                round($declared * static::DOMPDF_LINE_BOX_FACTOR, 3)
            );
        }

        return '<style>'.$css.'</style>';
    }

    /**
     * The template slug a `laravel-crm::pdfs.<slug>.<docType>` view refers to.
     */
    protected static function slugFor(string $view): ?string
    {
        return preg_match('/(?:^|[.:])pdfs\.([a-z0-9-]+)\./i', $view, $matches)
            ? $matches[1]
            : null;
    }

    /**
     * Insert `$markup` immediately after the document's opening `<head>`.
     */
    protected static function injectIntoHead(string $html, string $markup): string
    {
        return preg_replace_callback(
            '/<head(?:\s[^>]*)?>/i',
            fn (array $matches) => $matches[0].$markup,
            $html,
            1
        );
    }
}
