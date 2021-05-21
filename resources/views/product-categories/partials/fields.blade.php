<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => 'Name',
         'value' => old('name', $productCategory->name ?? null)
       ])

        @include('laravel-crm::partials.form.textarea',[
        'name' => 'description',
        'label' => 'Description',
         'rows' => 5,
        'value' => old('name', $productCategory->description ?? null)
      ])
    </div>
</div>