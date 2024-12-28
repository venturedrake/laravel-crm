<div class="form-group form-check">
    <input type="checkbox" class="form-check-input" id="checkbox_{{ $name }}" name="{{ $name }}">
    @isset($label)<label for="{{ $name }}[]" class="form-check-label">{{ $label }}@isset($required)<span class="required-label"> *</span>@endisset</label>@endisset
</div>