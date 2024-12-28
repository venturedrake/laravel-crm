@php
    if($model->id) {
        $fields = $model->fields;
    } else {
        $fields = \VentureDrake\LaravelCrm\Models\FieldModel::where('model', get_class($model))->get();
    }
@endphp
@foreach($fields as $fieldValueOrModel)  
    @if($fieldValueOrModel->field)
        @switch($fieldValueOrModel->field->type)
            @case('text')
                @include('laravel-crm::partials.form.text',[
                   'name' => 'fields['.$fieldValueOrModel->field->id.']',
                   'label' => ucfirst(__($fieldValueOrModel->field->name)),
                  'value' => old('fields['.$fieldValueOrModel->field->id.']', $fieldValueOrModel->value ?? null) 
               ])
                @break
            @case('textarea')
                @include('laravel-crm::partials.form.textarea',[
                   'name' => 'fields['.$fieldValueOrModel->field->id.']',
                   'label' => ucfirst(__($fieldValueOrModel->field->name)),
                   'rows' => 5,
                   'value' => old('fields['.$fieldValueOrModel->field->id.']', $fieldValueOrModel->value ?? null) 
                ])
                @break
            @case('select')
                @include('laravel-crm::partials.form.select',[
                   'name' => 'fields['.$fieldValueOrModel->field->id.']',
                   'label' => ucfirst(__($fieldValueOrModel->field->name)),
                   'options' => ['' => ''] + $fieldValueOrModel->field->fieldOptions->pluck('label','id')->toArray(),
                   'value' => old('fields['.$fieldValueOrModel->field->id.']', $fieldValueOrModel->value ?? null) 
               ])
                @break
            @case('checkbox')
                @include('laravel-crm::partials.form.checkbox',[
                   'name' => 'fields['.$fieldValueOrModel->field->id.']',
                   'label' => ucfirst(__($fieldValueOrModel->field->name)),
                   'value' => old('fields['.$fieldValueOrModel->field->id.']', $fieldValueOrModel->value ?? null) 
               ])
                @break
            @case('checkbox_multiple')
                <x-form-group label="{{ ucfirst(__($fieldValueOrModel->field->name)) }}">
                    @foreach($fieldValueOrModel->field->fieldOptions as $fieldOption)
                        <x-form-checkbox name="fields[{{ $fieldValueOrModel->field->id }}]" value="{{ $fieldOption->id }}" label="{{ $fieldOption->label }}" />
                    @endforeach
                </x-form-group>
                @break
            @case('radio')
                <x-form-group name="fields[{{ $fieldValueOrModel->field->id }}]" label="{{ ucfirst(__($fieldValueOrModel->field->name)) }}">
                    @foreach($fieldValueOrModel->field->fieldOptions as $fieldOption)
                        <x-form-radio name="fields[{{ $fieldValueOrModel->field->id }}]" value="{{ $fieldOption->id }}" label="{{ $fieldOption->label }}" />
                    @endforeach
                </x-form-group>
             @break
        @endswitch
    @endif
@endforeach  