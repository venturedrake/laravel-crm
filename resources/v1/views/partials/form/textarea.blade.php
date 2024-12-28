<div class="form-group @error($name) text-danger @enderror">
    @isset($label)<label for="{{ $name }}[]">{{ $label }}@isset($required)<span class="required-label"> *</span>@endisset</label>@endisset
    <textarea class="form-control @error($name) is-invalid @enderror" id="textarea_{{ $name }}" name="{{ $name }}" rows="{{ $rows ?? 3 }}" @include('laravel-crm::partials.form.attributes')>{{ $value ?? null }}</textarea>
    @error($name)
    <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
    @enderror
</div>