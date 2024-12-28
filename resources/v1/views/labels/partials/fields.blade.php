<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $label->name ?? null)
        ])
        
        <div id="input_hex" class="form-group @error('hex') text-danger @enderror">
            <label for="hex[]">{{ ucfirst(trans('laravel-crm::lang.color')) }}</label>
            <div class="input-group">
                <input id="input_hex" type="text" name="hex" value="{{ old('hex', (isset($label->hex) ? '#'.$label->hex : null)) }}" class="form-control @error('hex') is-invalid @enderror">
                <div class="input-group-append">
                    <span class="input-group-text colorpicker-input-addon" id="inputGroupPrepend"><i></i></span>
                </div>
            </div>
            @error('hex')
            <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
            @enderror
        </div>

        @include('laravel-crm::partials.form.textarea',[
        'name' => 'description',
        'label' => ucfirst(trans('laravel-crm::lang.description')),
         'rows' => 5,
        'value' => old('name', $label->description ?? null)
        ])
    </div>
</div>