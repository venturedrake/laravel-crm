<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
           'name' => 'person_name',
           'label' => 'Contact person',
           'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
           'value' => old('person_name', $deal->person->name ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
            'name' => 'organisation_name',
            'label' => 'Organisation',
            'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
            'value' => old('organisation_name',$deal->organisation->name ?? null)
        ])
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
        <h6 class="text-uppercase mt-4"><span class="fa fa-building" aria-hidden="true"></span> Organisation</h6>
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
        <h6 class="text-uppercase mt-4"><span class="fa fa-cart-arrow-down" aria-hidden="true"></span> Products</h6>
        <hr />
        ...
    </div>
</div>