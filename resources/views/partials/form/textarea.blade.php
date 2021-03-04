<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $label }}</label>
    <textarea class="form-control" id="textarea_{{ $name }}" name="{{ $name }}" rows="{{ $rows ?? 3 }}" @include('laravel-crm::partials.form.attributes')>{{ $value ?? null }}</textarea>
    @error($name)
    <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
    @enderror
</div>