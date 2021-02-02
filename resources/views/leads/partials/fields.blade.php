<div class="row">
    <div class="col-sm-6">
        @include('laravel-crm::partials.form.text',[
           'name' => 'person_name',
           'label' => 'Contact person',
           'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
           'value' => old('person_name', $lead->person_name ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
            'name' => 'organisation_name',
            'label' => 'Organisation',
            'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
            'value' => old('organisation_name',$lead->organisation_name ?? null)
        ])
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
        @include('laravel-crm::partials.form.select',[
                 'name' => 'user_assigned_id',
                 'label' => 'Owner',
                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                 'value' =>  old('user_assigned_id', $lead->assigned_user_id ?? auth()->user()->id),
              ])
    </div>
    <div class="col-sm-6">
        <h6><span class="fa fa-user" aria-hidden="true"></span> Person</h6>
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
                 'value' => old('phone_type', $phone->type ??  'work')
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
    </div>
</div>