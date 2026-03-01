Hi,

Here's quote {{ $quote->reference ?? null }} for {{ money($quote->total, $quote->currency) }} {{ $quote->currency ?? null }}.

Please advise if you accept the quote or if you have any questions, let us know.

[Online Quote Link]

Thanks,
{{ \VentureDrake\LaravelCrm\Models\Setting::where('name', 'organization_name')->first()->value }}
{{ ($quote->terms) ? "\nTerms:\n" . $quote->terms : null }}