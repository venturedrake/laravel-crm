<div class="row">
    <div class="col-sm-6 border-right">
        <div class="row">
            <div class="col-2">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'title',
                     'label' => 'Title',
                     'value' => old('title', $person->title ?? null)
                 ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'first_name',
                      'label' => 'First Name',
                      'value' => old('first_name', $person->first_name ?? null)
                  ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'last_name',
                   'label' => 'Last Name',
                   'value' => old('last_name', $person->last_name ?? null)
               ])
            </div>
        </div>
        <div class="row">
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                    'name' => 'middle_name',
                    'label' => 'Middle Name',
                    'value' => old('middle_name', $person->middle_name ?? null)
                ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.select',[
                   'name' => 'gender',
                   'label' => 'Gender',
                   'options' => [
                       '',
                       'male' => 'Male',
                       'female' => 'Female'
                       ],
                   'value' => old('gender', $person->gender ?? null)
               ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'birthday',
                      'label' => 'Birthday',
                      'value' => old('birthday', $person->birthday ?? null),
                      'attributes' => [
                          'autocomplete' => \Illuminate\Support\Str::random()
                       ]
                  ])
            </div>
        </div>
        
        @include('laravel-crm::partials.form.textarea',[
           'name' => 'description',
           'label' => 'Description',
           'rows' => 5,
           'value' => old('description', $person->description ?? null) 
        ])
        <span class="autocomplete">
            @include('laravel-crm::partials.form.hidden',[
               'name' => 'organisation_id',
               'value' => old('organisation_id', $person->organisation->id ?? $organisation->id ?? null)
            ])
            <script type="text/javascript">
                let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
            </script>
            @include('laravel-crm::partials.form.text',[
               'name' => 'organisation_name',
               'label' => 'Organisation',
               'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
               'value' => old('organisation_name',$person->organisation->name ?? $organisation->name ?? null),
               'attributes' => [
                    'autocomplete' => \Illuminate\Support\Str::random()
               ]
            ])
        </span>
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => 'Labels',
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all()),      
            'value' =>  old('labels', (isset($person)) ? $person->labels->pluck('id')->toArray() : null)
        ])
        @include('laravel-crm::partials.form.select',[
         'name' => 'user_owner_id',
         'label' => 'Owner',
         'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
         'value' =>  old('user_owner_id', $person->user_owner_id ?? auth()->user()->id),
       ])
    </div>
    <div class="col-sm-6">
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                 'name' => 'phone',
                 'label' => 'Phone',
                 'value' => old('phone', $phone->number ?? null)
              ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                 'name' => 'phone_type',
                 'label' => 'Type',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes(),
                 'value' => old('phone_type', $phone->type ??  'mobile')
              ])
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                 'name' => 'email',
                 'label' => 'Email',
                 'value' => old('email', $email->address ?? null),
              ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                 'name' => 'email_type',
                 'label' => 'Type',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes(),
                 'value' => old('email_type', $email->type ?? 'work')
              ])
            </div>
        </div>
        <hr />
        {{--@include('laravel-crm::partials.form.text',[
            'name' => 'address',
            'label' => 'Address',
            'value' => old('address', $address ?? null)
        ])--}}
        @include('laravel-crm::partials.form.text',[
           'name' => 'line1',
           'label' => 'Address Line 1',
           'value' => old('line1', $address->line1 ?? null)
        ])
        @include('laravel-crm::partials.form.text',[
           'name' => 'line2',
           'label' => 'Address Line 2',
           'value' => old('line2', $address->line2 ?? null)
        ])
        @include('laravel-crm::partials.form.text',[
           'name' => 'line3',
           'label' => 'Address Line 3',
           'value' => old('line3', $address->line3 ?? null)
        ])
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'suburb',
                   'label' => 'Suburb',
                   'value' => old('suburb', $address->suburb ?? null)
                ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'state',
                   'label' => 'State',
                   'value' => old('state', $address->state ?? null)
                ])
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'code',
                   'label' => 'Postcode',
                   'value' => old('code', $address->code ?? null)
                ])
            </div>
            <div class="col-sm-6">
                @include('laravel-crm::partials.form.select',[
                 'name' => 'country',
                 'label' => 'Country',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries(),
                 'value' => old('country', $address->country ?? 'United States')
              ])
            </div>
        </div>
    </div>
</div>