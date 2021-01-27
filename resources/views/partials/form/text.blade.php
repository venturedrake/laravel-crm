<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $title }}</label>
    @isset($prepend)
    <div class="input-group">
    <div class="input-group-prepend">
        <span class="input-group-text" id="inputGroupPrepend">{!! $prepend !!}</span>
    </div>
    @endisset    
    <input id="input_{{ $name }}" type="text" name="{{ $name }}" value="{{ old($name) ?? ${$name} ?? null }}" class="form-control @error($name) is-invalid @enderror">
    @error($name)
    <div class="text-danger">{{ $message }}</div>
    @enderror
    @isset($prepend)
    </div>
    @endisset
</div>        