<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $label }}</label>
    <textarea class="form-control" id="textarea_{{ $name }}" name="{{ $name }}" rows="{{ $rows ?? 3 }}">{{ old($name) ?? ${$name} ?? null }}</textarea>
    @error($name)
    <div class="text-danger">{{ $message }}</div>
    @enderror
</div>