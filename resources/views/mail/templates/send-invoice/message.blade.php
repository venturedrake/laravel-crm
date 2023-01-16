Hi,

Here's invoice {{ $invoice->invoice_id }} for {{ money($invoice->total, $invoice->currency) }}.

The amount outstanding of {{ money($invoice->total, $invoice->currency) }} is due on {{ $invoice->due_date->format('d M Y') }}.

View and pay your bill online: [Online Invoice Link]

From your online bill you can print a PDF, export a CSV, or create a free login and view your outstanding bills.

If you have any questions, please let us know.

Thanks,
{{ \VentureDrake\LaravelCrm\Models\Setting::where('name', 'organisation_name')->first()->value }}
@if($invoice->terms)
    
Terms
{{ $invoice->terms }}
@endif   