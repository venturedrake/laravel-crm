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
 */
class PortalDocument
{
    /**
     * Render `$view` with `$data` and return HTML suitable for a browser.
     *
     * The one adjustment over the PDF render is an injected
     * `<base href="{app root}/">`: a `srcdoc` iframe inherits the *parent*
     * page's URL as its base, so the document layout's relative
     * `vendor/laravel-crm/fonts/Nunito-*.ttf` @font-face sources would
     * otherwise resolve against `/p/invoices/{id}` and 404 — silently
     * dropping Nunito and making the web view diverge from the PDF it is
     * meant to mirror.
     *
     * This has to happen here rather than in `layouts/document.blade.php`,
     * because DomPDF resolves those same relative font paths itself against
     * its own base, with `dompdf.enable_remote` disabled — adding an absolute
     * `<base>` to the shared layout would change font resolution for every
     * PDF the package renders. Keep the two paths decoupled: the layout stays
     * PDF-shaped, and the browser-only fixup lives here.
     *
     * @param  string  $view  a `laravel-crm::pdfs.*` view path
     * @param  array<string, mixed>  $data
     */
    public static function html(string $view, array $data): string
    {
        $html = view($view, $data)->render();

        $base = '<base href="'.e(rtrim(url('/'), '/').'/').'">';

        return preg_replace_callback(
            '/<head(?:\s[^>]*)?>/i',
            fn (array $matches) => $matches[0].$base,
            $html,
            1
        );
    }
}
