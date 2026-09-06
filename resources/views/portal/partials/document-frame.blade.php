{{--
    Embeds the record's rendered PDF template (see Support\PortalDocument) in a
    same-origin, self-sizing iframe.

    `sandbox="allow-same-origin"` is load-bearing and deliberately omits
    `allow-scripts`. The PDF templates emit record content unescaped —
    `{!! nl2br($invoice->terms) !!}` and friends — which is harmless in DomPDF
    but would be live stored XSS in a browser on a public, unauthenticated
    page. Dropping script execution neuters it; keeping `allow-same-origin` is
    what lets the parent read `contentDocument` to sync the height. Do not
    "tidy" either half of that attribute away.

    Alpine is available here because layouts.portal loads Livewire. The
    ResizeObserver on the frame's documentElement re-syncs after webfonts land
    and on reflow, so the frame never scrolls or clips.

    `attach()` is called from both `init()` and the `load` handler because the
    two race. Livewire only starts Alpine on the parent's DOMContentLoaded, and
    the livewireScripts directive emits a blocking `<script src>` at the end of
    <body> — so the srcdoc document, which has no navigation to make, can fire
    `load` while the parser is still stalled on that fetch, i.e. before this
    element is ever initialised. Binding `load` alone would then leave `height`
    at 0 and collapse the frame to nothing on a public page, with no recovery.
    Re-attaching is cheap and idempotent: `attach()` disconnects any previous
    observer first, so running it twice is harmless.
--}}
<iframe
    x-data="{
        height: 0,
        observer: null,

        init() {
            /* The `load` we may have missed. `about:blank` also reports
               `complete` if the srcdoc navigation has not started yet, which
               measures 0 — harmless, because the real load re-attaches.
               Block comments, not `//`: an HTML minifier that collapses
               attribute whitespace would fold a line comment over the rest
               of this expression. */
            if (this.$el.contentDocument?.readyState === 'complete') {
                this.attach();
            }
        },

        destroy() {
            this.observer?.disconnect();
        },

        attach() {
            const doc = this.$el.contentDocument;

            if (! doc) return;

            const measure = () => this.height = doc.documentElement.scrollHeight;

            measure();
            this.observer?.disconnect();
            this.observer = new ResizeObserver(measure);
            this.observer.observe(doc.documentElement);
        },
    }"
    x-on:load="attach()"
    :style="`height: ${height}px`"
    sandbox="allow-same-origin"
    class="block w-full border-0"
    srcdoc="{{ $documentHtml }}"
></iframe>
