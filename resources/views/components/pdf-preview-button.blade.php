@php
    // One escaped JSON literal rather than three interpolated strings: titles
    // carry apostrophes ("O'Brien Ltd") that would otherwise break out of the
    // Alpine expression.
    $payload = \Illuminate\Support\Js::from([
        'url' => $url,
        'downloadUrl' => $downloadUrl,
        'title' => $title,
    ]);

    // Escaped separately rather than read off $payload: an Alpine expression
    // has to stay a single expression (no temp variable), because Alpine
    // compiles it as the right-hand side of an assignment.
    $previewUrl = \Illuminate\Support\Js::from($url);
@endphp

{{-- x-data is required for $dispatch to be available; the event bubbles up to
     the window listener on the single <x-crm-pdf-preview /> in the layout.

     The `crmPdfPreviewMounted` guard covers the host that published
     layouts/app.blade.php before this feature shipped: their layout has no
     <x-crm-pdf-preview /> in it, so the event would go nowhere and the button
     would be silently dead. Falling back to a new tab is no worse than a plain
     link — the preview route already serves the PDF inline, so the browser's
     own viewer renders it in place. --}}
<x-mary-button
    x-data
    icon="o-eye"
    tooltip="{{ ucfirst(__('laravel-crm::lang.preview')) }}"
    class="btn-sm btn-square btn-outline"
    @click="window.crmPdfPreviewMounted ? $dispatch('crm-pdf-preview', {{ $payload }}) : window.open({{ $previewUrl }}, '_blank', 'noopener')"
/>
