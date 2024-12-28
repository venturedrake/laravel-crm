<div class="form-group">
    <label for="crm_access">{{ ucfirst(__('laravel-crm::lang.primary')) }}@isset($required)<span class="required-label"> *</span>@endisset</label>
    <span class="form-control-toggle">
        <input id="checkbox_{{ $name }}" type="checkbox" name="{{ $name }}" {{ (isset($value) && $value == 1) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
    </span>
</div>