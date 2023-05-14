<div class="row">
    <div class="col-sm-5 border-right">
        @include('laravel-crm::partials.form.hidden',[
             'name' => 'lead_id',
             'value' => old('lead_id', $quote->lead->id ?? $lead->id ?? null),
        ])

        @livewire('quote-form',[
            'quote' => $quote ?? null,
            'generateTitle' => $generateTitle ?? true,
            'client' => $client ?? null,
            'organisation' => $organisation ?? null,
            'person' => $person ?? null
        ])
        
        @include('laravel-crm::partials.form.textarea',[
             'name' => 'description',
             'label' => ucfirst(__('laravel-crm::lang.description')),
             'rows' => 5,
             'value' => old('description', $quote->description ?? null) 
        ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'reference',
                      'label' => ucfirst(__('laravel-crm::lang.reference')),
                      'value' => old('amount', $quote->reference ?? null) 
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => ucfirst(__('laravel-crm::lang.currency')),
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', $quote->currency ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
                ])
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'issue_at',
                     'label' => ucfirst(__('laravel-crm::lang.issue_date')),
                     'value' => old('issue_at', (isset($quote->issue_at)) ? \Carbon\Carbon::parse($quote->issue_at)->format($dateFormat) : null),
                     'attributes' => [
                         'autocomplete' => \Illuminate\Support\Str::random()
                      ]
                 ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'expire_at',
                     'label' => ucfirst(__('laravel-crm::lang.expiry_date')),
                     'value' => old('expire_at', (isset($quote->expire_at)) ? \Carbon\Carbon::parse($quote->expire_at)->format($dateFormat) : null),
                     'attributes' => [
                         'autocomplete' => \Illuminate\Support\Str::random()
                      ]
                ])
            </div>
        </div>

        @include('laravel-crm::partials.form.textarea',[
             'name' => 'terms',
             'label' => ucfirst(__('laravel-crm::lang.terms')),
             'rows' => 5,
             'value' => old('terms', $quote->terms ?? $quoteTerms->value ?? null) 
        ])
        
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
            'value' =>  old('labels', (isset($quote)) ? $quote->labels->pluck('id')->toArray() : null)
        ])

        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_owner_id',
                 'label' => ucfirst(__('laravel-crm::lang.owner')),
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_owner_id', $quote->user_owner_id ?? auth()->user()->id),
              ])
    </div>
    <div class="col-sm-7">
        @livewire('quote-items',[
            'quote' => $quote ?? null,
            'products' => $quote->quoteProducts ?? null,
            'old' => old('products')
        ])
    </div>
</div>