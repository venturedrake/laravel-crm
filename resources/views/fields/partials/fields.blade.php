<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.select',[
        'name' => 'type',
        'label' => ucfirst(trans('laravel-crm::lang.type')),
        'options' => [
           'text' => 'Text' 
         ],
        'value' => old('type', $field->type ?? null)
       ])

        @include('laravel-crm::partials.form.select',[
        'name' => 'field_group_id',
        'label' => ucfirst(trans('laravel-crm::lang.group')),
        'options' => [''=>''] + \VentureDrake\LaravelCrm\Models\FieldGroup::pluck('name','id')->toArray(),
        'value' => old('field_group_id', $field->type ?? null)
       ])
        
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $field->name ?? null)
        ])

        @include('laravel-crm::partials.form.text',[
         'name' => 'default',
         'label' => ucfirst(trans('laravel-crm::lang.default')),
         'value' => old('default', $field->default ?? null)
        ])

        <div class="form-group">
            <label for="required">{{ ucfirst(__('laravel-crm::lang.required')) }}</label>
            <span class="form-control-toggle">
                 <input id="required" type="checkbox" name="required" {{ (isset($field) && $field->required == 1) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
            </span>
        </div>
    </div>
</div>