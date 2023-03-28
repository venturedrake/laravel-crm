<div class="form-group @error($name) text-danger @enderror">
    @isset($label)<label for="{{ $name }}[]">{{ $label }} @isset($required)<span class="required-label"> *</span>@endisset</label>@endisset
    <div class="autocomplete-control">
        @if(isset($prepend) || isset($append))
        <div class="input-group">
            @isset($prepend)
                <div class="input-group-prepend">
                    <span class="input-group-text" id="inputGroupPrepend">{!! $prepend !!}</span>
                </div>
            @endisset
        @endif   
        <input id="input_{{ $name }}" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ $value ?? null }}" class="form-control @error($name) is-invalid @enderror" @include('laravel-crm::partials.form.attributes') >
        @if(isset($prepend) || isset($append))
            @isset($append)
                <div class="input-group-append">
                    <span class="input-group-text" id="inputGroupPrepend">{!! $append !!}</span>
                </div>
            @endisset
        </div>
        @endif
        <span class="badge badge-primary autocomplete-new" @if((isset($new) && $new)) style="display: inline" @endif>New</span>
    </div>
    @error($name)
    <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
    @enderror
</div>        