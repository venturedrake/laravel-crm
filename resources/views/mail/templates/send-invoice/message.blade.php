Hi,

Here's invoice {{ $invoice->invoice_id }} for {{ money($invoice->total, $invoice->currency) }}.

@if($invoice->due_date)
The amount outstanding of {{ money($invoice->total, $invoice->currency) }} is due on {{ $invoice->due_date->format('d M Y') }}.
@else
The amount outstanding is {{ money($invoice->total, $invoice->currency) }}.
@endif

View and pay your invoice online: [Online Invoice Link]

From your online invoice you can print a PDF version.

If you have any questions, please let us know.

Thanks,
{{ app('laravel-crm.settings')->get('organization_name') }}
{{ ($invoice->terms) ? "\nTerms:\n" . $invoice->terms : null }}