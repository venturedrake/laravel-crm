<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => 'Name',
         'value' => old('name', $team->name ?? null)
       ])
    </div>
    <div class="col-sm-6">
        ...
    </div>
</div>