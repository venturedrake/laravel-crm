Hi,

Here's invoice {{ $invoice->invoice_id }} for {{ money($invoice->total, $invoice->currency) }}.

The amount outstanding of {{ money($invoice->total, $invoice->currency) }} is due on {{ $invoice->due_date->format('d M Y') }}.

View and pay your invoice online: [Online Invoice Link]

From your online invoice you can print a PDF version.

If you have any questions, please let us know.

Thanks,
{{ \VentureDrake\LaravelCrm\Models\Setting::where('name', 'organisation_name')->first()->value }}
@if($invoice->terms)
    
Terms
{{ $invoice->terms }}
@endif   