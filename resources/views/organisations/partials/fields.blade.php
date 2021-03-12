<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
          'name' => 'name',
          'label' => 'Name',
          'value' => old('name', $organisation->name ?? null)
        ])
        @include('laravel-crm::partials.form.textarea',[
           'name' => 'description',
           'label' => 'Description',
           'rows' => 5,
           'value' => old('description', $organisation->description ?? null) 
        ])
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => 'Labels',
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all()),      
            'value' =>  old('labels', (isset($organisation)) ? $organisation->labels->pluck('id')->toArray() : null)
        ])
        @include('laravel-crm::partials.form.select',[
             'name' => 'user_owner_id',
             'label' => 'Owner',
             'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
             'value' =>  old('user_owner_id', $organisation->user_owner_id ?? auth()->user()->id),
        ])
    </div>
    <div class="col-sm-6">
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
                   'name' => 'city',
                   'label' => 'Suburb',
                   'value' => old('city', $address->city ?? null)
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