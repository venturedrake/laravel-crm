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
               'options' => ['' => ''] + $fieldValue->field->fieldOptions->pluck('label','id')->toArray(),
               'value' => old('fields['.$fieldValue->field->id.']', $fieldValue->value ?? null) 
           ])
            @break
        @case('checkbox')
            @include('laravel-crm::partials.form.checkbox',[
               'name' => 'fields['.$fieldValue->field->id.']',
               'label' => ucfirst(__($fieldValue->field->name)),
               'value' => old('fields['.$fieldValue->field->id.']', $fieldValue->value ?? null) 
           ])
            @break
        @case('checkbox_multiple')
            <x-form-group label="{{ ucfirst(__($fieldValue->field->name)) }}">
                @foreach($fieldValue->field->fieldOptions as $fieldOption)
                    <x-form-checkbox name="fields[{{ $fieldValue->field->id }}]" value="{{ $fieldOption->id }}" label="{{ $fieldOption->label }}" />
                @endforeach
            </x-form-group>
            @break
        @case('radio')
            <x-form-group name="fields[{{ $fieldValue->field->id }}]" label="{{ ucfirst(__($fieldValue->field->name)) }}">
                @foreach($fieldValue->field->fieldOptions as $fieldOption)
                    <x-form-radio name="fields[{{ $fieldValue->field->id }}]" value="{{ $fieldOption->id }}" label="{{ $fieldOption->label }}" />
                @endforeach
            </x-form-group>
         @break
    @endswitch
@endforeach  