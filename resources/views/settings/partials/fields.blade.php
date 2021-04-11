<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'organisation_name',
         'label' => 'Organisation Name',
         'value' => old('organisation_name', $team->name ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
         'name' => 'language',
         'label' => 'Language',
         'value' => old('language', $team->language ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
         'name' => 'currency',
         'label' => 'Currency',
         'value' => old('currency', $team->currency ?? null)
       ])
    </div>
    <div class="col-sm-6">
        @include('laravel-crm::partials.form.text',[
         'name' => 'country',
         'label' => 'Country',
         'value' => old('country', $team->country ?? null)
       ])
        @include('laravel-crm::partials.form.text',[
        'name' => 'timezone',
        'label' => 'Timezone',
        'value' => old('timezone', $team->timezone ?? null)
      ])
    </div>
</div>