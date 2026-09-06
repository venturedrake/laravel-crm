{{--
    Bold PDF template layout.

    Visual language:
      * Full-width primary-colour (#05b3a9) header band across the top
      * White document title text inside the band
      * Brand mark / organization name also inside the band on the right
      * Tinted (soft-teal) totals box with prominent final total
      * Section headings picked out in the primary colour

    All CSS is scoped to `.bold-pdf` so styles never leak into other
    templates rendered in the same request.
--}}
@extends('laravel-crm::layouts.document')

@section('content')
<style>
    .bold-pdf {
        font-family: 'Nunito', 'Helvetica', sans-serif;
        color: #1f2937;
        font-size: 12px;
        line-height: 1.3;
    }

    .bold-pdf .bold-block {
        margin-bottom: 14px;
    }

    .bold-pdf table {
        width: 100%;
        border-collapse: collapse;
    }

    .bold-pdf .bold-header-band {
        background: #05b3a9;
        color: #ffffff;
        /* Previously bled 20px past the content box on each side via
           negative margins, so the band's edges did not line up with the
           meta/items tables below it. */
        margin: 0 0 22px 0;
    }

    /* The band's 18px/22px inset lives on the cells, not on the table.
       CSS ignores padding on a table in the collapsing border model (and
       .bold-pdf table sets border-collapse: collapse), so a browser drops
       it while DomPDF applies it — which left the portal page rendering
       the title flush to the band's left edge and the logo plate jammed
       against its right edge, where the PDF insets both by 22px.

       Declaring it per cell is honoured by both. The outer edges land in
       the same place because the title is left-aligned in the first cell
       and the logo right-aligned in the last; the extra padding on the two
       inner edges only widens an already-empty gutter between them. */
    .bold-pdf .bold-header-band td {
        vertical-align: middle;
        padding: 18px 22px;
        color: #ffffff;
    }

    .bold-pdf .bold-doc-title {
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
        letter-spacing: 0.02em;
    }

    .bold-pdf .bold-band-brand {
        text-align: right;
        color: #ffffff;
    }

    /* White plate behind the logo. No single band colour can suit every
       logo — a light logo disappears on a light band, a dark one on a dark
       band — but virtually every logo is drawn to sit on white, so backing
       it in white keeps it legible whatever the band colour is set to. */
    .bold-pdf .bold-band-logo-plate {
        display: inline-block;
        background: #ffffff;
        padding: 8px 12px;
        border-radius: 4px;
    }

    .bold-pdf .bold-band-brand img {
        max-height: 60px;
        max-width: 210px;
        display: block;
    }

    .bold-pdf .bold-band-brand-name {
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
    }

    /* 8px horizontal padding mirrors .bold-items th/td, so the meta,
       parties and note blocks share the line-item table's indent instead
       of sitting flush against the container edge. */
    .bold-pdf .bold-meta td {
        padding: 1px 8px;
        font-size: 11.5px;
        line-height: 1.15;
        color: #374151;
    }

    .bold-pdf .bold-meta .bold-meta-label {
        color: #05b3a9;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 11px;
        font-weight: 600;
        width: 42%;
    }

    .bold-pdf .bold-meta .bold-meta-value {
        color: #111827;
        font-weight: 500;
    }

    .bold-pdf .bold-parties td {
        vertical-align: top;
        padding: 0 12px 0 8px;
        width: 50%;
    }

    .bold-pdf .bold-parties td + td {
        padding: 0 8px 0 12px;
    }

    .bold-pdf .bold-party-heading {
        color: #05b3a9;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .bold-pdf .bold-party-body {
        color: #111827;
    }

    .bold-pdf .bold-items {
        margin-top: 8px;
    }

    .bold-pdf .bold-items thead th {
        background: #05b3a9;
        color: #ffffff;
        padding: 10px 8px;
        text-align: left;
        font-weight: 600;
        font-size: 11.5px;
        letter-spacing: 0.04em;
    }

    .bold-pdf .bold-items tbody td {
        padding: 10px 8px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
    }

    .bold-pdf .bold-items .bold-num {
        text-align: right;
        white-space: nowrap;
    }

    .bold-pdf .bold-totals-wrap {
        margin-top: 20px;
    }

    .bold-pdf .bold-totals-wrap > table {
        width: 100%;
    }

    .bold-pdf .bold-totals-spacer {
        width: 55%;
    }

    .bold-pdf .bold-totals {
        width: 45%;
        background: #e6f7f6;
        border: 1px solid #b7e5e1;
        border-radius: 4px;
    }

    .bold-pdf .bold-totals td {
        padding: 8px 14px;
        font-size: 12px;
    }

    .bold-pdf .bold-totals .bold-totals-label {
        color: #0e766e;
        text-align: right;
    }

    .bold-pdf .bold-totals .bold-totals-value {
        text-align: right;
        width: 40%;
        white-space: nowrap;
        color: #0b3f3c;
        font-weight: 500;
    }

    .bold-pdf .bold-totals .bold-totals-final td {
        background: #05b3a9;
        color: #ffffff;
        font-weight: 700;
        font-size: 14px;
    }

    .bold-pdf .bold-note {
        margin-top: 20px;
        padding-left: 8px;
    }

    .bold-pdf .bold-note-heading {
        color: #05b3a9;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .bold-pdf .bold-note-body {
        color: #1f2937;
    }
</style>

<div class="bold-pdf">
    @yield('boldContent')
</div>
@endsection
