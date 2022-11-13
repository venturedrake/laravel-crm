<div class="row">
    <div class="col border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'organisation_name',
         'label' => ucfirst(trans('laravel-crm::lang.organization_name')),
         'value' => old('organisation_name', $organisationName->value ?? null)
        ])
        
        @if($logoFile)
        <div class="mb-3">
            <img src=" {{ ($logoFile) ? asset('storage/'.$logoFile->value) : 'https://via.placeholder.com/140x90' }}" class="img-fluid" />
        </div>
        @endif
        @include('laravel-crm::partials.form.file',[
             'name' => 'logo',
             'label' => ucfirst(trans('laravel-crm::lang.logo')),
             'value' => old('logo', $timezone ?? null)
         ])
        
        @include('laravel-crm::partials.form.select',[
           'name' => 'language',
           'label' => ucfirst(trans('laravel-crm::lang.language')),
           'options' => ['english' => 'English'],
           'value' => old('language', $language->value ?? 'english')
        ])
    </div>
    <div class="col">
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
       @include('laravel-crm::partials.form.select',[
            'name' => 'timezone',
            'label' => ucfirst(trans('laravel-crm::lang.timezone')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timezones(),
            'value' => old('timezone', $timezone->value ?? null)
       ])
    </div>
</div>