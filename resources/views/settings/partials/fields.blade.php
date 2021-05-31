<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'organisation_name',
         'label' => ucfirst(trans('laravel-crm::lang.organisation_name')),
         'value' => old('organisation_name', $organisationName->value ?? null)
       ])
        @include('laravel-crm::partials.form.select',[
               'name' => 'language',
               'label' => ucfirst(trans('laravel-crm::lang.language')),
               'options' => ['english' => 'English'],
               'value' => old('language', $language->value ?? 'english')
            ])
    </div>
    <div class="col-sm-6">
        @include('laravel-crm::partials.form.select',[
                'name' => 'country',
                'label' => ucfirst(trans('laravel-crm::lang.country')),
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries(),
                'value' => old('country', $country->value  ?? 'United States')
             ])
        @include('laravel-crm::partials.form.select',[
           'name' => 'currency',
           'label' => ucfirst(trans('laravel-crm::lang.currency')),
           'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
           'value' => old('currency', $currency->value ?? 'USD')
       ])
      {{--  @include('laravel-crm::partials.form.select',[
            'name' => 'timezone',
            'label' => ucfirst(trans('laravel-crm::lang.timezone')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timezones(),
            'value' => old('timezone', $timezone ?? 'United States')
         ])--}}
    </div>
</div>