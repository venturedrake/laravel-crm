Hi,

Here's purchase order {{ $purchaseOrder->purchase_order_id }} for {{ money($purchaseOrder->total, $purchaseOrder->currency) }}.

View your purchase order online: [Online Purchase Order Link]

From your online invoice you can print a PDF version.

If you have any questions, please let us know.

Thanks,
{{ app('laravel-crm.settings')->get('organization_name') }}
{{ ($purchaseOrder->terms) ? "\nTerms:\n" . $purchaseOrder->terms : null }}