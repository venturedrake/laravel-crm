<div class="row">
    <div class="col-sm-5 border-right">
        @include('laravel-crm::partials.form.hidden',[
             'name' => 'lead_id',
             'value' => old('lead_id', $order->lead->id ?? $quote->lead->id ?? $lead->id ?? null),
        ])

        @include('laravel-crm::partials.form.hidden',[
             'name' => 'quote_id',
             'value' => old('quote_id', $order->quote->id ?? $quote->id ?? null),
        ])

        @if(isset($quote))

            @include('laravel-crm::partials.form.hidden',[
                'name' => 'client_id',
                'value' => old('client_id', $order->client->id ?? $quote->client->id ?? null),
            ])

            @include('laravel-crm::partials.form.hidden',[
                'name' => 'person_id',
                'value' => old('person_id', $order->person->id ?? $quote->person->id ?? null),
            ])

            @include('laravel-crm::partials.form.hidden',[
                'name' => 'organisation_id',
                'value' => old('organisation_id', $order->organisation->id ?? $quote->organisation->id ?? null),
            ])

            <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.client')) }}</h6>
            <hr />
            <p><span class="fa fa-address-card" aria-hidden="true"></span> @if($quote->client)<a href="{{ route('laravel-crm.clients.show',$quote->client) }}">{{ $quote->client->name }}</a>@endif </p>
            <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
            <hr />
            <p><span class="fa fa-building" aria-hidden="true"></span> @if($quote->organisation)<a href="{{ route('laravel-crm.organisations.show',$quote->organisation) }}">{{ $quote->organisation->name }}</a>@endif</p>
            <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</h6>
            <hr />
            <p><span class="fa fa-user" aria-hidden="true"></span> @if($quote->person)<a href="{{ route('laravel-crm.people.show',$quote->person) }}">{{ $quote->person->name }}</a>@endif </p>
            <h6 class="mt-4 text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
            <hr />
            
        @else

            @livewire('order-form',[
            'order' => $order ?? null,
            'client' => $client ?? null,
            'organisation' => $organisation ?? null,
            'person' => $person ?? null
            ])
            
        @endif    
        
        @include('laravel-crm::partials.form.textarea',[
             'name' => 'description',
             'label' => ucfirst(__('laravel-crm::lang.description')),
             'rows' => 5,
             'value' => old('description', $order->description ?? $quote->description ?? null)
        ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'reference',
                      'label' => ucfirst(__('laravel-crm::lang.reference')),
                      'value' => old('amount', $order->reference ?? $quote->reference  ?? null)
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => ucfirst(__('laravel-crm::lang.currency')),
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', $order->currency ?? $quote->currency ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
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
             'value' =>  old('user_owner_id', $order->user_owner_id ?? $quote->user_owner_id ?? auth()->user()->id),
          ])

        @livewire('address-edit', [
            'addresses' => $addresses ?? null,
            'old' => old('addresses'),
            'model' => 'order'
        ])
    </div>
    <div class="col-sm-7">
        
        @livewire('order-items',[
            'order' => $order ?? null,
            'products' => $order->orderProducts ?? $quote->quoteProducts ?? null,
            'old' => old('products'),
            'fromQuote' => (isset($quote)) ? $quote : false
        ])
    </div>
</div>
