<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $label }}</label>
    @isset($prepend)
    <div class="input-group">
    <div class="input-group-prepend">
        <span class="input-group-text" id="inputGroupPrepend">{!! $prepend !!}</span>
    </div>
    @endisset
        <select id="select_{{ $name }}" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror">
            @foreach($options as $optionKey => $optionName)
                <option value="{{ $optionKey }}" {{ ((old($name) && old($name) == $optionKey) || (isset(${$name}) && ${$name} == $optionKey) || (isset($default) && $default == $optionKey)) ? 'selected' : null }}>{{ $optionName }}</option>
            @endforeach    
        </select>
    @error($name)
    <div class="text-danger">{{ $message }}</div>
    @enderror
    @isset($prepend)
    </div>
    @endisset
</div>        