<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'organisation_name',
         'label' => 'Organisation Name',
         'value' => old('organisation_name', $organisationName->value ?? null)
       ])
        @include('laravel-crm::partials.form.select',[
               'name' => 'language',
               'label' => 'Language',
               'options' => ['english' => 'English'],
               'value' => old('language', $language->value ?? 'english')
            ])
    </div>
    <div class="col-sm-6">
        @include('laravel-crm::partials.form.select',[
                'name' => 'country',
                'label' => 'Country',
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries(),
                'value' => old('country', $country->value  ?? 'United States')
             ])
        @include('laravel-crm::partials.form.select',[
           'name' => 'currency',
           'label' => 'Currency',
           'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
           'value' => old('currency', $currency->value ?? 'USD')
       ])
      {{--  @include('laravel-crm::partials.form.select',[
            'name' => 'timezone',
            'label' => 'Timezone',
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timezones(),
            'value' => old('timezone', $timezone ?? 'United States')
         ])--}}
    </div>
</div>