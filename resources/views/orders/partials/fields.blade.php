<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.hidden',[
             'name' => 'lead_id',
             'value' => old('lead_id', $order->lead->id ?? $lead->id ?? null),
        ])
        <span class="autocomplete">
             @include('laravel-crm::partials.form.hidden',[
               'name' => 'person_id',
               'value' => old('person_id', $order->person->id ?? $person->id ?? null),
            ])
            <script type="text/javascript">
                let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\people() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
               'name' => 'person_name',
               'label' => ucfirst(__('laravel-crm::lang.contact_person')),
               'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
               'value' => old('person_name', $order->person->name ?? $person->name ?? null),
               'attributes' => [
                  'autocomplete' => \Illuminate\Support\Str::random()
               ]
            ])
        </span>
        <span class="autocomplete">
            @include('laravel-crm::partials.form.hidden',[
              'name' => 'organisation_id',
              'value' => old('organisation_id', $order->organisation->id ?? $organisation->id ??  null),
            ])
            <script type="text/javascript">
                let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
                'name' => 'organisation_name',
                'label' => ucfirst(__('laravel-crm::lang.organization')),
                'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
                'value' => old('organisation_name',$order->organisation->name ?? $organisation->name ?? null),
                'attributes' => [
                  'autocomplete' => \Illuminate\Support\Str::random()
               ]
            ])
        </span>    
        @include('laravel-crm::partials.form.text',[
            'name' => 'title',
            'label' => ucfirst(__('laravel-crm::lang.title')),
            'value' => old('title',$order->title ?? null)
        ])
        @include('laravel-crm::partials.form.textarea',[
             'name' => 'description',
             'label' => ucfirst(__('laravel-crm::lang.description')),
             'rows' => 5,
             'value' => old('description', $order->description ?? null) 
        ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'reference',
                      'label' => ucfirst(__('laravel-crm::lang.reference')),
                      'value' => old('amount', $order->reference ?? null) 
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => ucfirst(__('laravel-crm::lang.currency')),
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', $order->currency ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
                ])
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'issue_at',
                     'label' => ucfirst(__('laravel-crm::lang.issue_date')),
                     'value' => old('issue_at', (isset($order->issue_at)) ? \Carbon\Carbon::parse($order->issue_at)->format('Y/m/d') : null),
                     'attributes' => [
                         'autocomplete' => \Illuminate\Support\Str::random()
                      ]
                 ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'expire_at',
                     'label' => ucfirst(__('laravel-crm::lang.expiry_date')),
                     'value' => old('expire_at', (isset($order->expire_at)) ? \Carbon\Carbon::parse($order->expire_at)->format('Y/m/d') : null),
                     'attributes' => [
                         'autocomplete' => \Illuminate\Support\Str::random()
                      ]
                ])
            </div>
        </div>
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
            'value' =>  old('labels', (isset($order)) ? $order->labels->pluck('id')->toArray() : null)
        ])

        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_owner_id',
                 'label' => ucfirst(__('laravel-crm::lang.owner')),
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_owner_id', $order->user_owner_id ?? auth()->user()->id),
              ])
    </div>
    <div class="col-sm-6">
        <h6 class="text-uppercase section-h6-title"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.products')) }} {{--<span class="float-right"><a href="{{ (isset($order)) ? url(route('laravel-crm.order-products.create', $order)) : url(route('laravel-crm.order-products.create-product')) }}" class="btn btn-outline-secondary btn-sm btn-action-add-order-product"><span class="fa fa-plus" aria-hidden="true"></span></a></span>--}}</h6>
        <hr />
        <script type="text/javascript">
            let products =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\products() !!}
        </script>
        <span id="orderProducts">
            @if(isset($order) && method_exists($order,'orderProducts'))
                @foreach($order->orderProducts as $orderProduct)
                    @include('laravel-crm::order-products.partials.fields',[
                        'index' => $loop->index
                    ])
                @endforeach
            @endif    
        </span>
    </div>
</div>