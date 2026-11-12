{{-- The CSS reset strips the look of paragraphs, lists and links; a pasted
     email needs them back. Plain CSS, so it needs no asset rebuild. --}}
@once
    <style>
        .crm-email-body { white-space: normal; overflow-wrap: anywhere; }
        .crm-email-editor { min-height: 14rem; max-height: 28rem; overflow: auto; height: auto; }
        .crm-email-body p, .crm-email-body div { margin: 0 0 .5em; }
        .crm-email-body ul { list-style: disc; padding-left: 1.5em; margin: 0 0 .5em; }
        .crm-email-body ol { list-style: decimal; padding-left: 1.5em; margin: 0 0 .5em; }
        .crm-email-body a { text-decoration: underline; }
        .crm-email-body blockquote { border-left: 3px solid currentColor; opacity: .8; padding-left: .75em; margin: 0 0 .5em; }
        .crm-email-body h1, .crm-email-body h2, .crm-email-body h3 { font-weight: 700; margin: 0 0 .5em; }
        .crm-email-body table { border-collapse: collapse; margin: 0 0 .5em; }
        .crm-email-body td, .crm-email-body th { border: 1px solid currentColor; padding: .15em .4em; }
        .crm-email-body pre { white-space: pre-wrap; }
    </style>
@endonce
