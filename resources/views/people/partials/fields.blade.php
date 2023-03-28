<div class="row">
    <div class="col-sm-6 border-right">
        <div class="row">
            <div class="col-2">
                @include('laravel-crm::partials.form.text',[
                     'name' => 'title',
                     'label' => ucfirst(__('laravel-crm::lang.title')),
                     'value' => old('title', $person->title ?? null)
                 ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                      'name' => 'first_name',
                      'label' => ucfirst(__('laravel-crm::lang.first_name')),
                      'value' => old('first_name', $person->first_name ?? null),
                      'required' => 'true'
                  ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'last_name',
                   'label' => ucfirst(__('laravel-crm::lang.last_name')),
                   'value' => old('last_name', $person->last_name ?? null)
               ])
            </div>
        </div>
        <div class="row">
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                    'name' => 'middle_name',
                    'label' => ucfirst(__('laravel-crm::lang.middle_name')),
                    'value' => old('middle_name', $person->middle_name ?? null)
                ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.select',[
                   'name' => 'gender',
                   'label' => ucfirst(__('laravel-crm::lang.gender')),
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
                      'label' => ucfirst(__('laravel-crm::lang.birthday')),
                      'value' => old('birthday', $person->birthday ?? null),
                      'attributes' => [
                          'autocomplete' => \Illuminate\Support\Str::random()
                       ]
                  ])
            </div>
        </div>
        
        @include('laravel-crm::partials.form.textarea',[
           'name' => 'description',
           'label' => ucfirst(__('laravel-crm::lang.description')),
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
               'label' => ucfirst(__('laravel-crm::lang.organization')),
               'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
               'value' => old('organisation_name',$person->organisation->name ?? $organisation->name ?? null),
               'attributes' => [
                    'autocomplete' => \Illuminate\Support\Str::random()
               ]
            ])
        </span>
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
            'value' =>  old('labels', (isset($person)) ? $person->labels->pluck('id')->toArray() : null)
        ])
        @include('laravel-crm::partials.form.select',[
         'name' => 'user_owner_id',
         'label' => ucfirst(__('laravel-crm::lang.owner')),
         'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
         'value' =>  old('user_owner_id', $person->user_owner_id ?? auth()->user()->id),
       ])
    </div>
    <div class="col-sm-6">
        @livewire('phone-edit', [
        'phones' => $phones ?? null,
        'old' => old('phones')
        ])
        
        @livewire('email-edit', [
        'emails' => $emails ?? null,
        'old' => old('emails')
        ])

        @livewire('address-edit', [
        'addresses' => $addresses ?? null,
        'old' => old('addresses')
        ])
    </div>
</div>