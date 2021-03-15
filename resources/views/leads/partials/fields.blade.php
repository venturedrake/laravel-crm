<div class="row">
    <div class="col-sm-6 border-right">
         <span class="autocomplete">
               @include('laravel-crm::partials.form.hidden',[
                   'name' => 'person_id',
                   'value' => old('person_id', $lead->person->id ?? null),
                ])
               <script type="text/javascript">
                let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\people() !!}
               </script>
                 @include('laravel-crm::partials.form.text',[
                    'name' => 'person_name',
                    'label' => 'Contact person',
                    'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
                    'value' => old('person_name', $lead->person->name ?? $lead->person_name ?? null),
                    'attributes' => [
                       'autocomplete' => \Illuminate\Support\Str::random()
                    ],
                    'new' => ((isset($lead) && !$lead->person_id) ? true : false)
                ])
         </span>
        <span class="autocomplete">
            @include('laravel-crm::partials.form.hidden',[
             'name' => 'organisation_id',
             'value' => old('organisation_id', $lead->organisation->id ?? null),
            ])
            <script type="text/javascript">
                let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
                'name' => 'organisation_name',
                'label' => 'Organisation',
                'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
                'value' => old('organisation_name',$lead->organisation->name ?? $lead->organisation_name ?? null),
                'attributes' => [
                 'autocomplete' => \Illuminate\Support\Str::random()
               ],
                'new' => ((isset($lead) && !$lead->person_id) ? true : false)
            ])  
        </span>
        @include('laravel-crm::partials.form.text',[
            'name' => 'title',
            'label' => 'Title',
            'value' => old('title',$lead->title ?? null)
        ])
        @include('laravel-crm::partials.form.textarea',[
             'name' => 'description',
             'label' => 'Description',
             'rows' => 5,
             'value' => old('description', $lead->description ?? null) 
        ])

        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'amount',
                      'label' => 'Value',
                      'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                      'value' => old('amount', ((isset($lead->amount)) ? ($lead->amount / 100) : null) ?? null) 
                  ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                    'name' => 'currency',
                    'label' => 'Currency',
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                    'value' => old('currency', $lead->currency ?? 'USD')
                ])
            </div>
        </div>
        @include('laravel-crm::partials.form.multiselect',[
                    'name' => 'labels',
                    'label' => 'Labels',
                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all()),      
                    'value' =>  old('labels', (isset($lead)) ? $lead->labels->pluck('id')->toArray() : null)
                ])
        
        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_assigned_id',
                 'label' => 'Owner',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_assigned_id', $lead->assigned_user_id ?? auth()->user()->id),
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
    </div>
</div>