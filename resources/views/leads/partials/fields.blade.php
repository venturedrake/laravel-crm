<div class="row">
    <div class="col-sm-6 border-right">

        @livewire('live-lead-form',[
            'lead' => $lead ?? null,
            'generateTitle' => $generateTitle ?? true,
            'client' => $client ?? null,
            'organisation' => $organisation ?? null,
            'person' => $person ?? null
        ])
        
        @include('laravel-crm::partials.form.textarea',[
             'name' => 'description',
             'label' => ucfirst(__('laravel-crm::lang.description')),
             'rows' => 5,
             'value' => old('description', $lead->description ?? null) 
        ])

        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'amount',
                      'label' => ucfirst(__('laravel-crm::lang.value')),
                      'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                      'value' => old('amount', ((isset($lead->amount)) ? ($lead->amount / 100) : null) ?? null) 
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => ucfirst(__('laravel-crm::lang.currency')),
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', $lead->currency ?? \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD')
                ])
            </div>
        </div>
        @include('laravel-crm::partials.form.multiselect',[
                    'name' => 'labels',
                    'label' => ucfirst(__('laravel-crm::lang.labels')),
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
                    'value' =>  old('labels', (isset($lead)) ? $lead->labels->pluck('id')->toArray() : null)
                ])
        
        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_owner_id',
                 'label' => ucfirst(__('laravel-crm::lang.owner')),
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_owner_id', $lead->user_owner_id ?? auth()->user()->id),
              ])
    </div>
    <div class="col-sm-6">
        <h6 class="text-uppercase"><span class="fa fa-user" aria-hidden="true"></span> {{ strtoupper(__('laravel-crm::lang.person')) }}</h6>
        <hr />
        <span class="autocomplete-person">
            <div class="row">
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                     'name' => 'phone',
                     'label' => ucfirst(__('laravel-crm::lang.phone')),
                     'value' => old('phone', $phone->number ?? null),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.select',[
                     'name' => 'phone_type',
                     'label' => ucfirst(__('laravel-crm::lang.type')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes(),
                     'value' => old('phone_type', $phone->type ??  'mobile'),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                     'name' => 'email',
                     'label' => ucfirst(__('laravel-crm::lang.email')),
                     'value' => old('email', $email->address ?? null),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.select',[
                     'name' => 'email_type',
                     'label' => ucfirst(__('laravel-crm::lang.type')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes(),
                     'value' => old('email_type', $email->type ?? 'work'),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
            </div>
        </span>
        <h6 class="text-uppercase mt-4"><span class="fa fa-building" aria-hidden="true"></span> {{ strtoupper(__('laravel-crm::lang.organization')) }} </h6>
        <hr />
        <span class="autocomplete-organisation">
            {{--@include('laravel-crm::partials.form.text',[
                'name' => 'address',
                'label' => ucfirst(__('laravel-crm::lang.address')),
                'value' => old('address', $address ?? null)
            ])--}}
            @include('laravel-crm::partials.form.text',[
               'name' => 'line1',
               'label' => ucfirst(__('laravel-crm::lang.address_line_1')),
               'value' => old('line1', $address->line1 ?? null),
               'attributes' => [
                         'disabled' => 'disabled'
                     ]
            ])
            @include('laravel-crm::partials.form.text',[
               'name' => 'line2',
               'label' => ucfirst(__('laravel-crm::lang.address_line_2')),
               'value' => old('line2', $address->line2 ?? null),
               'attributes' => [
                         'disabled' => 'disabled'
                     ]
               
            ])
            @include('laravel-crm::partials.form.text',[
               'name' => 'line3',
               'label' => ucfirst(__('laravel-crm::lang.address_line_3')),
               'value' => old('line3', $address->line3 ?? null),
               'attributes' => [
                         'disabled' => 'disabled'
                     ]
            ])
            <div class="row">
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                       'name' => 'city',
                       'label' => ucfirst(__('laravel-crm::lang.suburb')),
                       'value' => old('city', $address->city ?? null),
                        'attributes' => [
                            'disabled' => 'disabled'
                         ]
                    ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                       'name' => 'state',
                       'label' => ucfirst(__('laravel-crm::lang.state')),
                       'value' => old('state', $address->state ?? null),
                       'attributes' => [
                                 'disabled' => 'disabled'
                        ]
                    ])
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                       'name' => 'code',
                       'label' => ucfirst(__('laravel-crm::lang.postcode')),
                       'value' => old('code', $address->code ?? null),
                        'attributes' => [
                         'disabled' => 'disabled'
                        ]
                    ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.select',[
                     'name' => 'country',
                     'label' => ucfirst(__('laravel-crm::lang.country')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries(),
                     'value' => old('country', $address->country ?? 'United States'),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
            </div>
        </span>
    </div>
</div>