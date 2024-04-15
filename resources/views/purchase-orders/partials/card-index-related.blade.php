@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-table')
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.number')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.reference')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.issue_date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.delivery_date')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.amount')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.sent')) }}</th>
                <th scope="col"></th> 
            </tr>
            </thead>
            <tbody>
            @foreach($purchaseOrders as $purchaseOrder)
               <tr @if(! $purchaseOrder->xeroPurchaseOrder) class="has-link" data-url="{{ url(route('laravel-crm.purchase-orders.show', $purchaseOrder)) }}" @endif>
                   <td>{{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id }}</td>
                   <td>{{ $purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference }}</td>
                   <td>{{ $purchaseOrder->issue_date->format($dateFormat) }}</td>
                   <td>{{ ($purchaseOrder->delivery_date) ? $purchaseOrder->delivery_date->format($dateFormat) : null }}</td>
                   <td>{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</td>
                   <td>
                       @if($purchaseOrder->sent == 1)
                           <span class="text-success">Sent</span>
                       @endif
                   </td>
                   <td>
                   @if($purchaseOrder->xeroPurchaseOrder)
                    <img src="/vendor/laravel-crm/img/xero-icon.png" height="30" />
                   @endif
                   </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    @endcomponent

@endcomponent
