<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $label }}</label>
    @isset($prepend)
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="inputGroupPrepend">{!! $prepend !!}</span>
            </div>
            @endisset
            <input id="input_{{ $name }}" type="password" name="{{ $name }}" value="{{ $value ?? null }}" class="form-control @error($name) is-invalid @enderror" @include('laravel-crm::partials.form.attributes') >
            @isset($prepend)
        </div>
    @endisset
    @error($name)
    <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
    @enderror
</div>        