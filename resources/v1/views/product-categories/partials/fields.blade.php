<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $productCategory->name ?? null)
       ])

        @include('laravel-crm::partials.form.textarea',[
        'name' => 'description',
        'label' => ucfirst(trans('laravel-crm::lang.description')),
         'rows' => 5,
        'value' => old('name', $productCategory->description ?? null)
      ])
    </div>
</div>