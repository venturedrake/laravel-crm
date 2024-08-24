<div class="row">
    <div class="col-sm-5 border-right">
        @include('laravel-crm::partials.form.hidden',[
             'name' => 'order_id',
             'value' => old('order_id', $invoice->order->id ?? $order->id ?? null),
        ])
        @empty($order)
        <span class="autocomplete">
             @include('laravel-crm::partials.form.hidden',[
               'name' => 'person_id',
               'value' => old('person_id', $purchaseOrder->person->id ?? $person->id ?? null),
            ])
            <script type="text/javascript">
                let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\people() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
               'name' => 'person_name',
               'label' => ucfirst(__('laravel-crm::lang.contact_person')),
               'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
               'value' => old('person_name', $purchaseOrder->person->name ?? $person->name ?? null),
               'attributes' => [
                  'autocomplete' => \Illuminate\Support\Str::random()
               ],
               'required' => 'true'
            ])
        </span>
        <span class="autocomplete">
            @include('laravel-crm::partials.form.hidden',[
              'name' => 'organisation_id',
              'value' => old('organisation_id', $purchaseOrder->organisation->id ?? $organisation->id ??  null),
            ])
            <script type="text/javascript">
                let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
                'name' => 'organisation_name',
                'label' => ucfirst(__('laravel-crm::lang.organization')),
                'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
                'value' => old('organisation_name',$purchaseOrder->organisation->name ?? $organisation->name ?? null),
                'attributes' => [
                  'autocomplete' => \Illuminate\Support\Str::random()
               ],
               'required' => 'true'
            ])
        </span>
        @endempty
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'reference',
                      'label' => ucfirst(__('laravel-crm::lang.reference')),
                      'value' => old('reference', $purchaseOrder->reference ?? $order->reference ?? null)
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                     'name' => 'currency',
                     'label' => ucfirst(__('laravel-crm::lang.currency')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                     'value' => old('currency', $purchaseOrder->currency ?? $order->currency ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
                 ])
               {{-- @include('laravel-crm::partials.form.hidden',[
                     'name' => 'prefix',
                     'value' => old('prefix', ($purchaseOrder->prefix ?? $prefix->value ?? 'INV-')),
                ])
                
                @if(! \Dcblogdev\Xero\Facades\Xero::isConnected())
                    @include('laravel-crm::partials.form.text',[
                        'name' => 'number',
                        'label' => ucfirst(__('laravel-crm::lang.invoice_number')),
                        'value' => old('number', $purchaseOrder->number ?? $number ?? null),
                        'prepend' => '<span aria-hidden="true">'.($purchaseOrder->prefix ?? $prefix->value ?? 'INV-').'</span>',
                        'required' => 'true'
                    ])
                @endif --}}   
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'issue_date',
                      'label' => ucfirst(__('laravel-crm::lang.issue_date')),
                      'value' => old('issue_date', (isset($purchaseOrder->issue_date)) ? \Carbon\Carbon::parse($purchaseOrder->issue_date)->format($dateFormat) : null),
                       'attributes' => [
                         'autocomplete' => \Illuminate\Support\Str::random()
                       ],
                       'required' => 'true'
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                       'name' => 'delivery_date',
                       'label' => ucfirst(__('laravel-crm::lang.delivery_date')),
                       'value' => old('delivery_date', (isset($purchaseOrder->delivery_date)) ? \Carbon\Carbon::parse($purchaseOrder->delivery_date)->format($dateFormat) : null),
                       'attributes' => [
                         'autocomplete' => \Illuminate\Support\Str::random()
                       ],
                   ])
            </div>
        </div>
        @include('laravel-crm::partials.form.textarea',[
            'name' => 'terms',
            'label' => ucfirst(__('laravel-crm::lang.terms')),
            'rows' => 5,
            'value' => old('terms', $purchaseOrder->terms ?? $purchaseOrderTerms->value ?? null)
       ])
        @livewire('delivery-details', [
            'purchaseOrder' => $purchaseOrder ?? null,
            'addresses' => $addresses,
            'purchaseOrderTerms' => $purchaseOrderTerms ?? null,
            'purchaseOrderDeliveryInstructions' => $purchaseOrderDeliveryInstructions ?? null
        ])
        {{--@include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),
            'value' =>  old('labels', (isset($purchaseOrder)) ? $purchaseOrder->labels->pluck('id')->toArray() : null)
        ])--}}

        {{--@include('laravel-crm::partials.form.select',[
                 'name' => 'user_owner_id',
                 'label' => ucfirst(__('laravel-crm::lang.owner')),
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_owner_id', $purchaseOrder->user_owner_id ?? auth()->user()->id),
              ])--}}

        @include('laravel-crm::fields.partials.model', ['model' => $purchaseOrder ?? new \VentureDrake\LaravelCrm\Models\PurchaseOrder()])
    </div>
    <div class="col-sm-7">
        @livewire('purchase-order-lines',[
            'purchaseOrder' => $purchaseOrder ?? null,
            'purchaseOrderLines' => $purchaseOrder->purchaseOrderLines ?? $order->orderProducts ?? null,
            'old' => old('purchaseOrderLines'),
            'fromOrder' => (isset($order)) ? $order : false
        ])
    </div>
</div>
