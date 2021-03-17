<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.hidden',[
             'name' => 'lead_id',
             'value' => old('lead_id', $deal->lead->id ?? $lead->id ?? null),
        ])
        <span class="autocomplete">
             @include('laravel-crm::partials.form.hidden',[
               'name' => 'person_id',
               'value' => old('person_id', $deal->person->id ?? $person->id ?? null),
            ])
            <script type="text/javascript">
                let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\people() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
               'name' => 'person_name',
               'label' => 'Contact person',
               'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
               'value' => old('person_name', $deal->person->name ?? $lead->person_name ?? $person->name ?? null),
               'attributes' => [
                  'autocomplete' => \Illuminate\Support\Str::random()
               ],
              'new' => ((isset($deal) && !$deal->person_id) || (isset($lead) && !$lead->person_id) ? true : false)
            ])
        </span>
        <span class="autocomplete">
            @include('laravel-crm::partials.form.hidden',[
              'name' => 'organisation_id',
              'value' => old('organisation_id', $deal->organisation->id ?? $organisation->id ??  null),
            ])
            <script type="text/javascript">
                let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
                'name' => 'organisation_name',
                'label' => 'Organisation',
                'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
                'value' => old('organisation_name',$deal->organisation->name ?? $lead->organisation_name ?? $organisation->name ?? null),
                'attributes' => [
                  'autocomplete' => \Illuminate\Support\Str::random()
               ],
               'new' => ((isset($deal) && !$deal->organisation_id) || (isset($lead) && !$lead->organisation_id) ? true : false)
            ])
        </span>    
        @include('laravel-crm::partials.form.text',[
            'name' => 'title',
            'label' => 'Title',
            'value' => old('title',$deal->title ?? null)
        ])
        @include('laravel-crm::partials.form.textarea',[
             'name' => 'description',
             'label' => 'Description',
             'rows' => 5,
             'value' => old('description', $deal->description ?? null) 
        ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'amount',
                      'label' => 'Value',
                      'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                      'value' => old('amount', ((isset($deal->amount)) ? ($deal->amount / 100) : null) ?? null) 
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => 'Currency',
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', $deal->currency ?? 'USD')
                ])
            </div>
        </div>
        @include('laravel-crm::partials.form.text',[
             'name' => 'expected_close',
             'label' => 'Expected close date',
             'value' => old('expected_close', (isset($deal->expected_close)) ? \Carbon\Carbon::parse($deal->expected_close)->format('Y/m/d') : null),
             'attributes' => [
                 'autocomplete' => \Illuminate\Support\Str::random()
              ]
         ])

        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => 'Labels',
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all()),      
            'value' =>  old('labels', (isset($deal)) ? $deal->labels->pluck('id')->toArray() : null)
        ])

        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_assigned_id',
                 'label' => 'Assigned to',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_assigned_id', $deal->assigned_user_id ?? auth()->user()->id),
              ])
    </div>
    <div class="col-sm-6">
        <h6 class="text-uppercase"><span class="fa fa-user" aria-hidden="true"></span> Person</h6>
        <hr />
        <span class="autocomplete-person">
            <div class="row">
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                     'name' => 'phone',
                     'label' => 'Phone',
                     'value' => old('phone', $phone->number ?? null),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.select',[
                     'name' => 'phone_type',
                     'label' => 'Type',
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
                     'label' => 'Email',
                     'value' => old('email', $email->address ?? null),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.select',[
                     'name' => 'email_type',
                     'label' => 'Type',
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes(),
                     'value' => old('email_type', $email->type ?? 'work'),
                     'attributes' => [
                         'disabled' => 'disabled'
                     ]
                  ])
                </div>
            </div>
        </span>
        <h6 class="text-uppercase mt-4"><span class="fa fa-building" aria-hidden="true"></span> Organisation</h6>
        <hr />
        <span class="autocomplete-organisation">
            {{--@include('laravel-crm::partials.form.text',[
                'name' => 'address',
                'label' => 'Address',
                'value' => old('address', $address ?? null)
            ])--}}
            @include('laravel-crm::partials.form.text',[
               'name' => 'line1',
               'label' => 'Address Line 1',
               'value' => old('line1', $address->line1 ?? null),
               'attributes' => [
                    'disabled' => 'disabled'
               ]
            ])
            @include('laravel-crm::partials.form.text',[
               'name' => 'line2',
               'label' => 'Address Line 2',
               'value' => old('line2', $address->line2 ?? null),
               'attributes' => [
                    'disabled' => 'disabled'
               ]
            ])
            @include('laravel-crm::partials.form.text',[
               'name' => 'line3',
               'label' => 'Address Line 3',
               'value' => old('line3', $address->line3 ?? null),
               'attributes' => [
                    'disabled' => 'disabled'
               ]
            ])
            <div class="row">
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                       'name' => 'city',
                       'label' => 'Suburb',
                       'value' => old('city', $address->city ?? null),
                       'attributes' => [
                            'disabled' => 'disabled'
                       ]
                    ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.text',[
                       'name' => 'state',
                       'label' => 'State',
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
                       'label' => 'Postcode',
                       'value' => old('code', $address->code ?? null),
                       'attributes' => [
                            'disabled' => 'disabled'
                        ]
                    ])
                </div>
                <div class="col-sm-6">
                    @include('laravel-crm::partials.form.select',[
                     'name' => 'country',
                     'label' => 'Country',
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries(),
                     'value' => old('country', $address->country ?? 'United States'),
                     'attributes' => [
                            'disabled' => 'disabled'
                       ]
                  ])
                </div>
            </div>
        </span>
       {{-- <h6 class="text-uppercase mt-4"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> Products</h6>
        <hr />
        ...--}}
    </div>
</div>