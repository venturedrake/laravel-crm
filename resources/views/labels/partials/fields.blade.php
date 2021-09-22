<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $label->name ?? null)
       ])

        @include('laravel-crm::partials.form.text',[
         'name' => 'hex',
         'label' => ucfirst(trans('laravel-crm::lang.color')),
         'value' => old('hex', $label->hex ?? null),
         'prepend' => '#'
       ])

        @include('laravel-crm::partials.form.textarea',[
        'name' => 'description',
        'label' => ucfirst(trans('laravel-crm::lang.description')),
         'rows' => 5,
        'value' => old('name', $label->description ?? null)
      ])
    </div>
</div>