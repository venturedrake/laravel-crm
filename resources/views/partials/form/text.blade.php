<div class="form-group @error($name) text-danger @enderror">
    <label for="{{ $name }}">{{ $label }}</label>
    <div class="autocomplete-control">
        @isset($prepend)
        <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text" id="inputGroupPrepend">{!! $prepend !!}</span>
        </div>
        @endisset    
        <input id="input_{{ $name }}" type="text" name="{{ $name }}" value="{{ $value ?? null }}" class="form-control @error($name) is-invalid @enderror" @include('laravel-crm::partials.form.attributes') >
        @isset($prepend)
        </div>
        @endisset
        <span class="badge badge-primary autocomplete-new" @if((isset($new) && $new)) style="display: inline" @endif>New</span>
    </div>
    @error($name)
    <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
    @enderror
</div>        