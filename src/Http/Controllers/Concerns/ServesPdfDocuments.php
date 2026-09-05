<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Concerns;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * The shared response envelope for the five document controllers that serve
 * one generated PDF two ways: `download()` as an attachment, `preview()`
 * inline for the pdf.js drawer.
 */
trait ServesPdfDocuments
{
    /**
     * Wrap already-rendered PDF bytes in a response.
     */
    protected function pdfResponse(string $output, string $filename, string $disposition = HeaderUtils::DISPOSITION_ATTACHMENT): Response
    {
        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $this->pdfDisposition($disposition, $filename),
            'Content-Length' => strlen($output),
        ]);
    }

    /**
     * Build the Content-Disposition header for a PDF filename.
     *
     * Goes through HeaderUtils::makeDisposition() rather than interpolating
     * the name into `filename="..."`, because these names are not ours: they
     * carry the admin-editable `quote_prefix` / `order_prefix` / … setting
     * and, for invoices and purchase orders, a number straight out of Xero.
     * A quote character in either would close the quoted string early and
     * truncate the download name; a non-ASCII one needs the RFC 6266
     * `filename*` form plus an ASCII fallback. This is what Barryvdh's own
     * PDF::download()/stream() do, and what these controllers had before
     * download() and preview() were split onto a shared buildPdf().
     */
    protected function pdfDisposition(string $disposition, string $filename): string
    {
        // makeDisposition() throws on path separators, which would turn a
        // merely odd prefix into a 500 on a route that used to respond.
        $filename = str_replace(['/', '\\'], '-', $filename);

        // The `filename=` half has to be printable ASCII and free of '%'.
        // Str::ascii transliterates what it can and drops the rest, so a name
        // that folds away to nothing (or to a control character) falls back to
        // a generic one rather than making makeDisposition() throw.
        $fallback = str_replace('%', '', Str::ascii($filename));

        if (! preg_match('/^[\x20-\x7e]+$/', $fallback)) {
            $fallback = 'document.pdf';
        }

        return HeaderUtils::makeDisposition($disposition, $filename, $fallback);
    }
}
