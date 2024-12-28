Hi,

Here's purchase order {{ $purchaseOrder->purchase_order_id }} for {{ money($purchaseOrder->total, $purchaseOrder->currency) }}.

If you have any questions, please let us know.

Thanks,
{{ \VentureDrake\LaravelCrm\Models\Setting::where('name', 'organisation_name')->first()->value }}
@if($purchaseOrder->terms)
    
Terms
{{ $purchaseOrder->terms }}
@endif   