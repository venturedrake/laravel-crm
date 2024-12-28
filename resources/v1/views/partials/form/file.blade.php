<div class="form-group @error($name) text-danger @enderror">
    @isset($label)<label for="{{ $name }}[]">{{ $label }}@isset($required)<span class="required-label"> *</span>@endisset</label>@endisset
    <div class="custom-file">
        <input type="file" class="custom-file-input @error($name) is-invalid @enderror" id="file_{{ $random ?? null }}" name="{{ $name }}" @include('laravel-crm::partials.form.attributes')>
        <label wire:ignore class="custom-file-label" for="customFile">{{ ucfirst(__('laravel-crm::lang.choose_file')) }}</label>
    </div>
    @error($name)
    <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
    @enderror
</div>