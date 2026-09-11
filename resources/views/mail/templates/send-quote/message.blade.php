Hi,

Here's quote {{ $quote->reference ?? null }} for {{ money($quote->total, $quote->currency) }} {{ $quote->currency ?? null }}.

Please advise if you accept the quote or if you have any questions, let us know.

[Online Quote Link]

Thanks,
{{ app('laravel-crm.settings')->get('organization_name') }}
{{ ($quote->terms) ? "\nTerms:\n" . $quote->terms : null }}