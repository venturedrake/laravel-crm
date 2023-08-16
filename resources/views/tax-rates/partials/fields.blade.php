<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $taxRate->name ?? null)
       ])

        @include('laravel-crm::partials.form.text',[
         'name' => 'rate',
         'label' => ucfirst(trans('laravel-crm::lang.rate')),
         'value' => old('rate', $taxRate->rate ?? null),
         'append' => '<span class="fa fa-percent" aria-hidden="true"></span>',
       ])

        @include('laravel-crm::partials.form.textarea',[
        'name' => 'description',
        'label' => ucfirst(trans('laravel-crm::lang.description')),
        'rows' => 5,
        'value' => old('name', $taxRate->description ?? null)
      ])
    </div>
</div>