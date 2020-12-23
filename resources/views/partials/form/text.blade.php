<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $title }}</label>
    <input id="input_{{ $name }}" type="text" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror">
    @error($name)
    <div class="text-danger">{{ $message }}</div>
    @enderror
</div>