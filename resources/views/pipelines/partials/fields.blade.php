<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $pipeline->name ?? null),
         'required' => 'true'
       ])
    </div>
</div>