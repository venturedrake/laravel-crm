@foreach($model->fields as $fieldValue)
    @switch($fieldValue->field->type)
        @case('text')
            @include('laravel-crm::partials.form.text',[
               'name' => 'fields['.$fieldValue->field->id.']',
               'label' => ucfirst(__($fieldValue->field->name)),
              'value' => old('fields['.$fieldValue->field->id.']', $fieldValue->value ?? null) 
           ])
            @break
        @case('textarea')
            @include('laravel-crm::partials.form.textarea',[
               'name' => 'fields['.$fieldValue->field->id.']',
               'label' => ucfirst(__($fieldValue->field->name)),
               'rows' => 5,
               'value' => old('fields['.$fieldValue->field->id.']', $fieldValue->value ?? null) 
            ])
            @break
        @case('select')
            @include('laravel-crm::partials.form.select',[
               'name' => 'fields['.$fieldValue->field->id.']',
               'label' => ucfirst(__($fieldValue->field->name)),
                'options' => [],
               'value' => old('fields['.$fieldValue->field->id.']', $fieldValue->value ?? null) 
           ])
            @break
    @endswitch
@endforeach  